<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryRate extends LogisticsModel
{
    protected $table    = 'lg_delivery_rates';
    protected $fillable = [
        'org_id', 'zone_id', 'name',
        'base_rate', 'rate_per_unit', 'rate_per_kg',
        'min_charge', 'max_charge', 'currency', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_rate'      => 'decimal:4',
            'rate_per_unit'  => 'decimal:4',
            'rate_per_kg'    => 'decimal:4',
            'min_charge'     => 'decimal:4',
            'max_charge'     => 'decimal:4',
            'is_active'      => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }

    public function calculate(int $unitCount, float $weightKg): float
    {
        $cost = (float) $this->base_rate
            + ($unitCount * (float) $this->rate_per_unit)
            + ($weightKg  * (float) $this->rate_per_kg);

        $cost = max($cost, (float) $this->min_charge);

        if ($this->max_charge !== null) {
            $cost = min($cost, (float) $this->max_charge);
        }

        return round($cost, 4);
    }
}
