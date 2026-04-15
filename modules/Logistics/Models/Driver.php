<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends LogisticsModel
{
    protected $table    = 'lg_drivers';
    protected $fillable = [
        'org_id', 'actor_id', 'name', 'phone',
        'license_number', 'license_expiry',
        'status', 'availability', 'last_seen_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'last_seen_at'   => 'datetime',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(DeliveryRun::class, 'driver_id');
    }

    public function activeRun(): ?DeliveryRun
    {
        return $this->runs()->whereIn('status', ['dispatched', 'in_progress'])->first();
    }

    public function isAvailable(): bool { return $this->availability === 'online' && $this->status === 'active'; }
    public function isOnRun(): bool     { return $this->availability === 'on_run'; }
}
