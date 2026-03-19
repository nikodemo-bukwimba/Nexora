<?php

namespace Modules\PharmaMarketing\Models;

class DailyReport extends PharmaModel
{
    protected $table    = 'pm_daily_reports';
    protected $fillable = [
        'org_id', 'officer_actor_id', 'report_date', 'status',
        'planned_visits', 'completed_visits', 'new_customers', 'samples_distributed',
        'summary', 'challenges', 'achievements', 'next_day_plan',
        'reviewed_by', 'submitted_at', 'reviewed_at', 'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'report_date'  => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at'  => 'datetime',
        ];
    }

    public function isDraft(): bool     { return $this->status === 'draft'; }
    public function isSubmitted(): bool { return $this->status === 'submitted'; }
    public function isApproved(): bool  { return $this->status === 'approved'; }
}
