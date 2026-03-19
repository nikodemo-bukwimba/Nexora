<?php

namespace Modules\Commerce\Models;

class ShippingRate extends CommerceModel
{
    protected $table    = 'shipping_rates';
    protected $fillable = [
        'org_id', 'name', 'method', 'calculation_type',
        'base_rate', 'rate_per_kg', 'rate_per_value_percent',
        'free_shipping_threshold', 'min_weight_kg', 'max_weight_kg',
        'currency', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_rate'                 => 'decimal:4',
            'rate_per_kg'              => 'decimal:4',
            'rate_per_value_percent'   => 'decimal:4',
            'free_shipping_threshold'  => 'decimal:4',
            'is_active'                => 'boolean',
        ];
    }

    public function calculate(float $orderValue, float $weightKg): float
    {
        if ($this->free_shipping_threshold && $orderValue >= $this->free_shipping_threshold) {
            return 0.0;
        }

        return match ($this->calculation_type) {
            'flat'         => (float) $this->base_rate,
            'weight_based' => (float) $this->base_rate + ($weightKg * (float) $this->rate_per_kg),
            'value_based'  => (float) $this->base_rate + ($orderValue * ((float) $this->rate_per_value_percent / 100)),
            default        => (float) $this->base_rate,
        };
    }
}
