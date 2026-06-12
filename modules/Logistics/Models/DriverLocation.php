<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLocation extends LogisticsModel
{
    public $timestamps  = false;
    protected $table    = 'lg_driver_locations';
    protected $fillable = [
        'driver_id', 'run_id', 'stop_id',
        'latitude', 'longitude', 'accuracy_meters',
        'speed_kmh', 'heading_degrees', 'source', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude'         => 'decimal:7',
            'longitude'        => 'decimal:7',
            'accuracy_meters'  => 'decimal:2',
            'speed_kmh'        => 'decimal:2',
            'heading_degrees'  => 'decimal:2',
            'recorded_at'      => 'datetime',
            'created_at'       => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(DeliveryRun::class, 'run_id');
    }
}