<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends FinanceModel
{
    protected $fillable = [
        'name', 'description', 'price', 'currency',
        'billing_cycle', 'is_active', 'is_public', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price'     => 'decimal:4',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    public function limits(): HasMany
    {
        return $this->hasMany(SubscriptionPlanLimit::class, 'plan_id');
    }

    public function orgSubscriptions(): HasMany
    {
        return $this->hasMany(OrgSubscription::class, 'plan_id');
    }

    public function getLimit(string $featureKey): mixed
    {
        $limit = $this->limits->firstWhere('feature_key', $featureKey);
        return $limit ? $limit->limit_value : null;
    }
}
