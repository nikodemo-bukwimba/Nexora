<?php

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFulfillment extends CommerceModel
{
    protected $table    = 'order_fulfillments';
    protected $fillable = [
        'order_id', 'status', 'carrier', 'tracking_number',
        'tracking_url', 'weight_kg', 'shipped_at',
        'delivered_at', 'estimated_delivery_at', 'notes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at'            => 'datetime',
            'delivered_at'          => 'datetime',
            'estimated_delivery_at' => 'datetime',
            'metadata'              => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
