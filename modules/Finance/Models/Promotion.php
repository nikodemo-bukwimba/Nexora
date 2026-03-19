<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends FinanceModel
{
    protected $fillable = [
        'org_id', 'code', 'name', 'description', 'type', 'value',
        'currency', 'min_order_amount', 'max_discount_amount',
        'usage_limit', 'usage_count', 'usage_limit_per_actor',
        'is_active', 'starts_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'value'               => 'decimal:4',
            'min_order_amount'    => 'decimal:4',
            'max_discount_amount' => 'decimal:4',
            'is_active'           => 'boolean',
            'starts_at'           => 'datetime',
            'expires_at'          => 'datetime',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class, 'promotion_id');
    }

    public function isValid(): bool
    {
        if (! $this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) return false;
        return true;
    }

    public function usageCountForActor(string $actorId): int
    {
        return $this->usages()->where('actor_id', $actorId)->count();
    }
}
