<?php

namespace Modules\PharmaMarketing\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\PharmaMarketing\Models\WeeklyPlan;
use Modules\PharmaMarketing\Models\WeeklyPlanItem;

class WeeklyPlanService
{
    public function create(string $orgId, string $officerActorId, array $data): WeeklyPlan
    {
        return DB::connection('pharma_marketing')->transaction(function () use ($orgId, $officerActorId, $data) {
            $weekStart = $data['week_start_date'];
            $weekEnd   = date('Y-m-d', strtotime($weekStart . ' +6 days'));

            $existing = WeeklyPlan::where('officer_actor_id', $officerActorId)
                ->where('week_start_date', $weekStart)
                ->first();

            if ($existing) {
                return $existing->fresh(['items']);
            }

            $plan = WeeklyPlan::create([
                'org_id'           => $orgId,
                'officer_actor_id' => $officerActorId,
                'week_start_date'  => $weekStart,
                'week_end_date'    => $weekEnd,
                'status'           => 'draft',
                'objectives'       => $data['objectives'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] ?? [] as $i => $item) {
                WeeklyPlanItem::create(array_merge($item, [
                    'plan_id'    => $plan->id,
                    'sort_order' => $i,
                    'status'     => 'planned',
                ]));
            }

            return $plan->fresh(['items']);
        });
    }

    public function get(string $id): WeeklyPlan
    {
        return WeeklyPlan::with(['items'])->findOrFail($id);
    }

    public function list(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        return WeeklyPlan::where('org_id', $orgId)
            ->when(isset($filters['officer_id']), fn($q) => $q->where('officer_actor_id', $filters['officer_id']))
            ->when(isset($filters['status']),     fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['week']),        fn($q) => $q->where('week_start_date', $filters['week']))
            ->with(['items'])
            ->orderBy('week_start_date', 'desc')
            ->paginate($perPage);
    }

    public function update(string $planId, string $officerActorId, array $data): WeeklyPlan
    {
        $plan = WeeklyPlan::where('officer_actor_id', $officerActorId)
            ->whereIn('status', ['draft', 'rejected'])
            ->findOrFail($planId);

        $updates = array_filter([
            'objectives' => $data['objectives'] ?? $plan->objectives,
            'notes'      => $data['notes'] ?? $plan->notes,
        ], fn($v) => $v !== null);

        // Recalculate week_end_date if week_start_date is being changed
        if (isset($data['week_start_date'])) {
            $updates['week_start_date'] = $data['week_start_date'];
            $updates['week_end_date']   = date('Y-m-d', strtotime($data['week_start_date'] . ' +6 days'));
        }

        $plan->update($updates);
        return $plan->fresh(['items']);
    }

    public function delete(string $planId, string $officerActorId): void
    {
        $plan = WeeklyPlan::where('officer_actor_id', $officerActorId)
            ->whereIn('status', ['draft', 'rejected'])
            ->findOrFail($planId);

        $plan->items()->delete();
        $plan->delete();
    }

    public function addItem(string $planId, array $data): WeeklyPlanItem
    {
        $plan = WeeklyPlan::findOrFail($planId);
        if (! $plan->isDraft()) {
            throw new \RuntimeException('Can only add items to a draft plan.');
        }

        $count = WeeklyPlanItem::where('plan_id', $planId)->count();
        return WeeklyPlanItem::create(array_merge($data, [
            'plan_id'    => $planId,
            'sort_order' => $count,
            'status'     => 'planned',
        ]));
    }

    public function removeItem(string $planId, string $itemId, string $officerActorId): void
    {
        $plan = WeeklyPlan::where('officer_actor_id', $officerActorId)
            ->whereIn('status', ['draft', 'rejected'])
            ->findOrFail($planId);

        $item = WeeklyPlanItem::where('plan_id', $plan->id)
            ->findOrFail($itemId);

        $item->delete();
    }

    public function submit(string $planId, string $officerActorId): WeeklyPlan
    {
        $plan = WeeklyPlan::where('officer_actor_id', $officerActorId)
            ->whereIn('status', ['draft', 'rejected'])
            ->findOrFail($planId);

        $plan->update(['status' => 'submitted', 'submitted_at' => now()]);
        return $plan->fresh(['items']);
    }

    public function approve(string $planId, string $headOfficerActorId): WeeklyPlan
    {
        $plan = WeeklyPlan::where('status', 'submitted')->findOrFail($planId);
        $plan->update([
            'status'      => 'approved',
            'approved_by' => $headOfficerActorId,
            'approved_at' => now(),
        ]);
        return $plan->fresh(['items']);
    }

    public function reject(string $planId, string $headOfficerActorId, string $reason): WeeklyPlan
    {
        $plan = WeeklyPlan::where('status', 'submitted')->findOrFail($planId);
        $plan->update([
            'status'           => 'rejected',
            'approved_by'      => $headOfficerActorId,
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
        ]);
        return $plan->fresh(['items']);
    }
}