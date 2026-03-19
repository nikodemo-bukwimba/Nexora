<?php

namespace Modules\Platform\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Platform\Contracts\Services\EventBusInterface;
use Modules\Platform\Events\PlatformEvent;
use Symfony\Component\Uid\Ulid;

class EventBus implements EventBusInterface
{
    public function fire(PlatformEvent $event, ?string $actorId = null): void
    {
        $eventName    = $event->fullEventName();
        $dispatchMode = $event instanceof \Illuminate\Contracts\Queue\ShouldQueue
            ? 'async'
            : 'sync';

        // 1. Log the dispatch
        $this->writeDispatchLog($eventName, $event->moduleName(), $event->payload(), $actorId, $dispatchMode);

        // 2. Dispatch via Laravel's native dispatcher
        // ShouldQueue on the event class handles async automatically
        try {
            event($event);
        } catch (\Throwable $e) {
            $this->updateDispatchLogStatus($eventName, 'failed');
            Log::error("EventBus dispatch failed: {$eventName}", [
                'error'   => $e->getMessage(),
                'payload' => $event->payload(),
            ]);
            throw $e;
        }
    }

    public function register(
        string $eventName,
        string $module,
        string $dispatchMode = 'async',
        ?array $payloadSchema = null
    ): void {
        DB::connection('platform')
            ->table('event_registry')
            ->insertOrIgnore([
                'id'             => (string) new Ulid(),
                'name'           => $eventName,
                'module'         => $module,
                'dispatch_mode'  => $dispatchMode,
                'payload_schema' => $payloadSchema ? json_encode($payloadSchema) : null,
                'is_active'      => true,
                'created_at'     => now(),
            ]);
    }

    private function writeDispatchLog(
        string $eventName,
        string $module,
        array $payload,
        ?string $actorId,
        string $dispatchMode
    ): void {
        try {
            DB::connection('platform')
                ->table('event_dispatch_log')
                ->insert([
                    'id'            => (string) new Ulid(),
                    'event_name'    => $eventName,
                    'module'        => $module,
                    'payload'       => json_encode($payload),
                    'actor_id'      => $actorId,
                    'dispatch_mode' => $dispatchMode,
                    'status'        => 'dispatched',
                    'fired_at'      => now(),
                    'created_at'    => now(),
                ]);
        } catch (\Throwable $e) {
            // Never let logging failure prevent event dispatch
            Log::warning("EventBus: failed to write dispatch log for {$eventName}: " . $e->getMessage());
        }
    }

    private function updateDispatchLogStatus(string $eventName, string $status): void
    {
        try {
            DB::connection('platform')
                ->table('event_dispatch_log')
                ->where('event_name', $eventName)
                ->orderBy('created_at', 'desc')
                ->limit(1)
                ->update(['status' => $status]);
        } catch (\Throwable $e) {
            Log::warning("EventBus: failed to update dispatch log status: " . $e->getMessage());
        }
    }
}
