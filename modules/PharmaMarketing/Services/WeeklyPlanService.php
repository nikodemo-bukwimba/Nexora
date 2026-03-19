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
            $weekStart = $data['week_start_date']; // expect YYYY-MM-DD Monday
            $weekEnd   = date('Y-m-d', strtotime($weekStart . ' +6 days'));

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

    public function submit(string $planId, string $officerActorId): WeeklyPlan
    {
        $plan = WeeklyPlan::where('officer_actor_id', $officerActorId)
            ->where('status', 'draft')
            ->findOrFail($planId);

        $plan->update(['status' => 'submitted', 'submitted_at' => now()]);
        return $plan->fresh();
    }

    public function approve(string $planId, string $headOfficerActorId): WeeklyPlan
    {
        $plan = WeeklyPlan::where('status', 'submitted')->findOrFail($planId);
        $plan->update([
            'status'      => 'approved',
            'approved_by' => $headOfficerActorId,
            'approved_at' => now(),
        ]);
        return $plan->fresh();
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
        return $plan->fresh();
    }
}
