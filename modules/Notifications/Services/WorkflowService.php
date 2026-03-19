<?php

namespace Modules\Notifications\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Models\WorkflowDefinition;
use Modules\Notifications\Models\WorkflowRun;
use Modules\Notifications\Models\WorkflowStepLog;

class WorkflowService
{
    public function __construct(
        protected NotificationService $notifications
    ) {}

    /**
     * Create a workflow definition.
     *
     * Steps format:
     * [
     *   ['type' => 'notify', 'name' => 'Notify buyer', 'actor_field' => 'buyer_actor_id', 'notification_type' => 'order.shipped', 'title' => 'Your order shipped', 'body' => 'Order {{order_number}} is on its way.'],
     *   ['type' => 'wait',   'name' => 'Wait 24h', 'duration_hours' => 24],
     *   ['type' => 'notify', 'name' => 'Delivery followup', 'actor_field' => 'buyer_actor_id', 'notification_type' => 'order.followup', 'title' => 'How was your order?', 'body' => 'Rate your experience.'],
     * ]
     */
    public function create(array $data): WorkflowDefinition
    {
        return WorkflowDefinition::create($data);
    }

    public function get(string $id): WorkflowDefinition
    {
        return WorkflowDefinition::with(['runs'])->findOrFail($id);
    }

    public function listForOrg(?string $orgId, int $perPage): LengthAwarePaginator
    {
        return WorkflowDefinition::where(function ($q) use ($orgId) {
                $q->whereNull('org_id')->orWhere('org_id', $orgId);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Trigger all active workflows that listen to a given event.
     * Called by the EventBus listener.
     */
    public function triggerByEvent(string $eventName, array $payload): void
    {
        $definitions = WorkflowDefinition::where('trigger_event', $eventName)
            ->where('is_active', true)
            ->get();

        foreach ($definitions as $definition) {
            $this->startRun($definition, $eventName, $payload);
        }
    }

    /**
     * Start and execute a workflow run synchronously.
     * For async execution, dispatch a job instead.
     */
    public function startRun(WorkflowDefinition $definition, string $eventName, array $payload): WorkflowRun
    {
        $run = WorkflowRun::create([
            'workflow_definition_id' => $definition->id,
            'trigger_event'          => $eventName,
            'trigger_payload'        => $payload,
            'status'                 => 'running',
            'current_step'           => 0,
            'context'                => $payload,
        ]);

        try {
            $this->executeSteps($run, $definition->steps);
            $run->update(['status' => 'completed', 'completed_at' => now()]);
        } catch (\Throwable $e) {
            $run->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
            Log::error("Workflow run failed: {$definition->name}", ['run_id' => $run->id, 'error' => $e->getMessage()]);
        }

        return $run->fresh(['stepLogs']);
    }

    public function getRun(string $runId): WorkflowRun
    {
        return WorkflowRun::with(['stepLogs', 'definition'])->findOrFail($runId);
    }

    public function listRuns(string $definitionId, int $perPage): LengthAwarePaginator
    {
        return WorkflowRun::where('workflow_definition_id', $definitionId)
            ->with(['stepLogs'])
            ->orderBy('started_at', 'desc')
            ->paginate($perPage);
    }

    // ── Step execution ─────────────────────────────────────────

    private function executeSteps(WorkflowRun $run, array $steps): void
    {
        $context = $run->context ?? [];

        foreach ($steps as $index => $step) {
            $log = WorkflowStepLog::create([
                'run_id'      => $run->id,
                'step_index'  => $index,
                'step_type'   => $step['type'],
                'step_name'   => $step['name'] ?? null,
                'status'      => 'running',
                'input'       => $step,
                'started_at'  => now(),
            ]);

            $run->update(['current_step' => $index]);

            try {
                $output = $this->executeStep($step, $context);
                $context = array_merge($context, $output['context_updates'] ?? []);

                $log->update([
                    'status'       => 'completed',
                    'output'       => $output,
                    'completed_at' => now(),
                ]);
            } catch (\Throwable $e) {
                $log->update([
                    'status'       => 'failed',
                    'error'        => $e->getMessage(),
                    'completed_at' => now(),
                ]);
                throw $e;
            }
        }
    }

    private function executeStep(array $step, array $context): array
    {
        return match ($step['type']) {
            'notify'  => $this->executeNotifyStep($step, $context),
            'wait'    => $this->executeWaitStep($step, $context),
            'webhook' => $this->executeWebhookStep($step, $context),
            default   => throw new \RuntimeException("Unknown step type: {$step['type']}"),
        };
    }

    private function executeNotifyStep(array $step, array $context): array
    {
        // Resolve actor_id from context using actor_field
        $actorId = $context[$step['actor_field']] ?? null;

        if (! $actorId) {
            return ['skipped' => true, 'reason' => "actor_field '{$step['actor_field']}' not in context"];
        }

        // Interpolate template variables in title/body
        $title = $this->interpolate($step['title'] ?? '', $context);
        $body  = $this->interpolate($step['body']  ?? '', $context);

        $notification = $this->notifications->send(
            actorId:   $actorId,
            type:      $step['notification_type'],
            title:     $title,
            body:      $body,
            actionUrl: $step['action_url'] ?? null,
            refType:   $step['ref_type'] ?? null,
            refId:     $context[$step['ref_id_field'] ?? ''] ?? null,
        );

        return ['notification_id' => $notification->id, 'actor_id' => $actorId];
    }

    private function executeWaitStep(array $step, array $context): array
    {
        // In sync execution, wait steps are logged but not actually delayed.
        // For real delays, dispatch a queued job with a delay.
        $hours = $step['duration_hours'] ?? 0;
        return ['waited_hours' => $hours, 'note' => 'Sync mode: wait logged but not enforced. Use queued jobs for real delays.'];
    }

    private function executeWebhookStep(array $step, array $context): array
    {
        $url     = $this->interpolate($step['url'] ?? '', $context);
        $payload = array_merge($step['payload'] ?? [], $context);

        try {
            $response = \Illuminate\Support\Facades\Http::post($url, $payload);
            return ['status_code' => $response->status(), 'ok' => $response->ok()];
        } catch (\Throwable $e) {
            throw new \RuntimeException("Webhook failed: {$e->getMessage()}");
        }
    }

    /**
     * Replace {{variable}} placeholders in template strings.
     */
    private function interpolate(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($context) {
            return $context[$matches[1]] ?? $matches[0];
        }, $template);
    }
}
