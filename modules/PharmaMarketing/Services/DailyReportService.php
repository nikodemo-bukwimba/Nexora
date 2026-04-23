<?php

namespace Modules\PharmaMarketing\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Platform\Contracts\Services\OrgScopeResolverInterface;
use Modules\PharmaMarketing\Models\DailyReport;
use Modules\PharmaMarketing\Models\FieldVisit;

class DailyReportService
{
    public function __construct(
        protected OrgScopeResolverInterface $scope
    ) {}

    public function getOrCreateForToday(string $orgId, string $officerActorId): DailyReport
    {
        $date   = now()->toDateString();
        $report = DailyReport::where('officer_actor_id', $officerActorId)
            ->where('report_date', $date)
            ->first();

        if (! $report) {
            $year  = now()->year;
            $count = DailyReport::whereYear('report_date', $year)->count() + 1;

            $report = DailyReport::create([
                'org_id'           => $orgId,
                'officer_actor_id' => $officerActorId,
                'report_date'      => $date,
                'report_number'    => 'RPT-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT),
                'status'           => 'draft',
            ]);
        }

        $this->syncVisitCounts($report);
        return $report->fresh();
    }

    public function update(string $reportId, string $officerActorId, array $data): DailyReport
    {
        $report = DailyReport::where('id', $reportId)
            ->where('officer_actor_id', $officerActorId)
            ->where('status', 'draft')
            ->firstOrFail();

        $allowed = array_intersect_key($data, array_flip([
            'summary', 'challenges', 'achievements', 'next_day_plan',
            'key_outcomes', 'challenges_faced', 'custom_body', 'is_customized',
            'visited_customers',
        ]));

        if (isset($allowed['key_outcomes']))     $allowed['summary']    = $allowed['key_outcomes'];
        if (isset($allowed['challenges_faced'])) $allowed['challenges'] = $allowed['challenges_faced'];

        $report->update($allowed);
        return $report->fresh();
    }

    public function submit(string $reportId, string $officerActorId): DailyReport
    {
        $report = DailyReport::where('id', $reportId)
            ->where('officer_actor_id', $officerActorId)
            ->whereIn('status', ['draft', 'rejected'])
            ->firstOrFail();

        $this->syncVisitCounts($report);
        $report->update(['status' => 'submitted', 'submitted_at' => now()]);
        return $report->fresh();
    }

    public function approve(string $reportId, string $reviewerActorId, ?string $notes): DailyReport
    {
        $report = DailyReport::where('status', 'submitted')->findOrFail($reportId);
        $report->update([
            'status'       => 'approved',
            'reviewed_by'  => $reviewerActorId,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);
        return $report->fresh();
    }

    public function reject(string $reportId, string $reviewerActorId, string $notes): DailyReport
    {
        $report = DailyReport::where('status', 'submitted')->findOrFail($reportId);
        $report->update([
            'status'       => 'rejected',
            'reviewed_by'  => $reviewerActorId,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);
        return $report->fresh();
    }

    /**
     * List reports with org-tree awareness.
     *
     * Root admin   → sees reports from ALL branches
     * Branch user  → sees reports from their branch only
     *
     * Root admin can filter by branch:
     *   $filters['branch_id'] = '01KMQ1...'
     */
    public function list(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        $orgIds = $this->scope->scopeIds($orgId, $filters['branch_id'] ?? null);

        return DailyReport::whereIn('org_id', $orgIds)
            ->when(isset($filters['officer_id']), fn($q) => $q->where('officer_actor_id', $filters['officer_id']))
            ->when(isset($filters['status']),     fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date']),       fn($q) => $q->where('report_date', $filters['date']))
            ->when(isset($filters['from']),       fn($q) => $q->where('report_date', '>=', $filters['from']))
            ->when(isset($filters['to']),         fn($q) => $q->where('report_date', '<=', $filters['to']))
            ->orderBy('report_date', 'desc')
            ->paginate($perPage);
    }

    private function syncVisitCounts(DailyReport $report): void
    {
        $visits = FieldVisit::where('officer_actor_id', $report->officer_actor_id)
            ->whereDate('check_in_at', $report->report_date)
            ->get();

        $report->update([
            'completed_visits'    => $visits->where('status', 'completed')->count(),
            'samples_distributed' => $visits->sum(fn($v) => $v->products->sum('samples_given')),
        ]);
    }
}