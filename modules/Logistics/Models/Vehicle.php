<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends LogisticsModel
{
    protected $table    = 'lg_vehicles';
    protected $fillable = [
        'org_id', 'registration', 'type', 'make', 'model',
        'year', 'payload_kg', 'max_stops', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['payload_kg' => 'decimal:2'];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(DeliveryRun::class, 'vehicle_id');
    }

    public function isAvailable(): bool { return $this->status === 'active'; }
}
