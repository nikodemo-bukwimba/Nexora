<?php

namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyPlanItem extends PharmaModel
{
    protected $table    = 'pm_weekly_plan_items';
    protected $fillable = [
        'plan_id', 'planned_date', 'item_type',
        'customer_id', 'customer_name', 'title',
        'objective', 'planned_start_time', 'planned_end_time',
        'sort_order', 'status', 'visit_id', 'notes',
    ];

    protected function casts(): array
    {
        return ['planned_date' => 'date'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(WeeklyPlan::class, 'plan_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(FieldVisit::class, 'visit_id');
    }

    public function isCustomerVisit(): bool { return $this->item_type === 'customer_visit'; }
    public function isCompleted(): bool     { return $this->status === 'completed'; }
}
