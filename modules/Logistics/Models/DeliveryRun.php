<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryRun extends LogisticsModel
{
    protected $table    = 'lg_delivery_runs';
    protected $fillable = [
        'run_number', 'org_id', 'driver_id', 'vehicle_id', 'dispatched_by',
        'status', 'scheduled_date', 'scheduled_start_time',
        'dispatched_at', 'started_at', 'completed_at',
        'total_stops', 'delivered_count', 'failed_count',
        'notes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date'      => 'date',
            'dispatched_at'       => 'datetime',
            'started_at'          => 'datetime',
            'completed_at'        => 'datetime',
            'metadata'            => 'array',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(DeliveryStop::class, 'run_id')->orderBy('stop_sequence');
    }

    public function isDraft(): bool        { return $this->status === 'draft'; }
    public function isDispatched(): bool   { return $this->status === 'dispatched'; }
    public function isInProgress(): bool   { return $this->status === 'in_progress'; }
    public function isCompleted(): bool    { return in_array($this->status, ['completed', 'partially_completed']); }
}
