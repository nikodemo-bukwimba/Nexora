<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierShipment extends LogisticsModel
{
    protected $table    = 'lg_courier_shipments';
    protected $fillable = [
        'org_id', 'courier_account_id', 'order_id', 'order_number',
        'tracking_number', 'waybill_number', 'tracking_url',
        'status', 'courier_status',
        'weight_kg', 'unit_count', 'declared_value', 'shipping_cost', 'currency',
        'recipient_name', 'recipient_phone', 'delivery_address',
        'booked_at', 'picked_up_at', 'delivered_at', 'failed_at',
        'estimated_delivery_at', 'failure_reason', 'courier_events',
    ];

    protected function casts(): array
    {
        return [
            'shipping_cost'        => 'decimal:4',
            'declared_value'       => 'decimal:4',
            'booked_at'            => 'datetime',
            'picked_up_at'         => 'datetime',
            'delivered_at'         => 'datetime',
            'failed_at'            => 'datetime',
            'estimated_delivery_at' => 'datetime',
            'courier_events'       => 'array',
        ];
    }

    public function courierAccount(): BelongsTo
    {
        return $this->belongsTo(CourierAccount::class, 'courier_account_id');
    }

    public function isDelivered(): bool { return $this->status === 'delivered'; }
    public function isFailed(): bool    { return $this->status === 'failed'; }
}
