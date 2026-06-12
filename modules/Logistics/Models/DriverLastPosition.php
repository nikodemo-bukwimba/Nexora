<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLastPosition extends Model
{
    public $incrementing  = false;
    public $timestamps    = false;
    protected $connection = 'logistics';
    protected $table      = 'lg_driver_last_positions';
    protected $primaryKey = 'driver_id';
    protected $keyType    = 'string';

    protected $fillable = [
        'driver_id', 'run_id', 'stop_id',
        'latitude', 'longitude', 'accuracy_meters',
        'speed_kmh', 'heading_degrees', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude'        => 'decimal:7',
            'longitude'       => 'decimal:7',
            'accuracy_meters' => 'decimal:2',
            'speed_kmh'       => 'decimal:2',
            'heading_degrees' => 'decimal:2',
            'recorded_at'     => 'datetime',
            'updated_at'      => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}