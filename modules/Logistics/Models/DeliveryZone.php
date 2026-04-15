<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends LogisticsModel
{
    protected $table    = 'lg_delivery_zones';
    protected $fillable = ['org_id', 'name', 'code', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(DeliveryRate::class, 'zone_id');
    }
}
