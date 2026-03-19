<?php

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturn extends CommerceModel
{
    protected $table    = 'order_returns';
    protected $fillable = [
        'order_id', 'requested_by', 'reason', 'status', 'resolution',
        'refund_amount', 'currency', 'reviewed_by', 'reviewed_at',
        'completed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:4',
            'reviewed_at'   => 'datetime',
            'completed_at'  => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
