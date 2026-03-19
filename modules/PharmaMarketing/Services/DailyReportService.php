<?php

namespace Modules\PharmaMarketing\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\PharmaMarketing\Models\DailyReport;
use Modules\PharmaMarketing\Models\FieldVisit;

class DailyReportService
{
    /**
     * Get or create today's draft report for an officer.
     * Auto-populates visit counts from actual visit data.
     */
    public function getOrCreateForToday(string $orgId, string $officerActorId): DailyReport
    {
        $date   = now()->toDateString();
        $report = DailyReport::where('officer_actor_id', $officerActorId)
            ->where('report_date', $date)
            ->first();

        if (! $report) {
            $report = DailyReport::create([
                'org_id'            => $orgId,
                'officer_actor_id'  => $officerActorId,
                'report_date'       => $date,
                'status'            => 'draft',
            ]);
        }

        // Auto-sync visit counts
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
        ]));
        $report->update($allowed);
        return $report->fresh();
    }

    public function submit(string $reportId, string $officerActorId): DailyReport
    {
        $report = DailyReport::where('id', $reportId)
            ->where('officer_actor_id', $officerActorId)
            ->where('status', 'draft')
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

    public function list(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        return DailyReport::where('org_id', $orgId)
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
