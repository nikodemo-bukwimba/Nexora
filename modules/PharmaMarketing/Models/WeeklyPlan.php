<?php

namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyPlan extends PharmaModel
{
    protected $table    = 'pm_weekly_plans';
    protected $fillable = [
        'org_id', 'officer_actor_id',
        'week_start_date', 'week_end_date',
        'status', 'objectives', 'notes',
        'approved_by', 'submitted_at', 'approved_at',
        'rejected_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
            'week_end_date'   => 'date',
            'submitted_at'    => 'datetime',
            'approved_at'     => 'datetime',
            'rejected_at'     => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(WeeklyPlanItem::class, 'plan_id')->orderBy('planned_date')->orderBy('sort_order');
    }

    public function isDraft(): bool     { return $this->status === 'draft'; }
    public function isSubmitted(): bool { return $this->status === 'submitted'; }
    public function isApproved(): bool  { return $this->status === 'approved'; }
    public function isActive(): bool    { return $this->status === 'active'; }
}
