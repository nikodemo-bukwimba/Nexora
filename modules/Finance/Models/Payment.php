<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends FinanceModel
{
    protected $fillable = [
        'invoice_id', 'payer_actor_id', 'payee_actor_id',
        'amount', 'currency', 'status', 'method',
        'gateway', 'gateway_payment_id', 'gateway_status',
        'gateway_fee', 'net_amount', 'paid_at',
        'failure_reason', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:4',
            'gateway_fee' => 'decimal:4',
            'net_amount'  => 'decimal:4',
            'paid_at'     => 'datetime',
            'metadata'    => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function commissionRecord(): HasOne
    {
        return $this->hasOne(CommissionRecord::class, 'payment_id');
    }

    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isFailed(): bool    { return $this->status === 'failed'; }
}
