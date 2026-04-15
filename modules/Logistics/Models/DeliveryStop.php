<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeliveryStop extends LogisticsModel
{
    protected $table    = 'lg_delivery_stops';
    protected $fillable = [
        'run_id', 'org_id', 'order_id', 'order_number',
        'recipient_name', 'recipient_phone', 'address', 'city', 'zone_id',
        'latitude', 'longitude', 'stop_sequence', 'status',
        'unit_count', 'weight_kg', 'rate_id', 'delivery_cost', 'currency',
        'estimated_arrival_at', 'arrived_at', 'delivered_at', 'failed_at',
        'failure_reason', 'failure_notes', 'rescheduled_date',
        'return_status', 'returned_at',
        'delivery_latitude', 'delivery_longitude', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'delivery_cost'        => 'decimal:4',
            'weight_kg'            => 'decimal:4',
            'estimated_arrival_at' => 'datetime',
            'arrived_at'           => 'datetime',
            'delivered_at'         => 'datetime',
            'failed_at'            => 'datetime',
            'rescheduled_date'     => 'date',
            'returned_at'          => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(DeliveryRun::class, 'run_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(StopStatusLog::class, 'stop_id')->orderBy('created_at');
    }

    public function proof(): HasOne
    {
        return $this->hasOne(DeliveryProof::class, 'stop_id');
    }

    public function isDelivered(): bool  { return $this->status === 'delivered'; }
    public function isFailed(): bool     { return $this->status === 'failed'; }
    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isEnRoute(): bool    { return $this->status === 'en_route'; }
}
