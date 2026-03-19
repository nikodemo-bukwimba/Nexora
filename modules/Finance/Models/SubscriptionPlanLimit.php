<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPlanLimit extends FinanceModel
{
    protected $table    = 'subscription_plan_limits';
    protected $fillable = [
        'plan_id', 'feature_key', 'feature_group', 'limit_value', 'description',
    ];

    protected function casts(): array
    {
        return ['limit_value' => 'array'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Check if this limit allows the given usage count.
     * -1 in limit_value.value means unlimited.
     */
    public function allows(int $currentUsage): bool
    {
        $value = $this->limit_value['value'] ?? null;
        $enabled = $this->limit_value['enabled'] ?? null;

        if ($enabled !== null) return (bool) $enabled;
        if ($value === null) return true;
        if ($value === -1) return true;      // unlimited

        return $currentUsage < $value;
    }
}
