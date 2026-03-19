<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRecord extends FinanceModel
{
    protected $table    = 'commission_records';
    protected $fillable = [
        'commission_config_id', 'payment_id', 'actor_id',
        'transaction_amount', 'commission_rate', 'commission_amount',
        'currency', 'status', 'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_amount'  => 'decimal:4',
            'commission_rate'     => 'decimal:6',
            'commission_amount'   => 'decimal:4',
            'collected_at'        => 'datetime',
        ];
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(CommissionConfig::class, 'commission_config_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
