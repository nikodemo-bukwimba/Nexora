<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgSubscription extends FinanceModel
{
    protected $fillable = [
        'org_id', 'plan_id', 'status',
        'trial_ends_at', 'current_period_start', 'current_period_end',
        'cancelled_at', 'expires_at',
        'gateway_subscription_id', 'gateway', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at'         => 'datetime',
            'current_period_start'  => 'datetime',
            'current_period_end'    => 'datetime',
            'cancelled_at'          => 'datetime',
            'expires_at'            => 'datetime',
            'metadata'              => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    public function isExpired(): bool
    {
        return $this->current_period_end->isPast();
    }
}
