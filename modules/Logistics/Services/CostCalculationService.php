<?php

namespace Modules\Logistics\Services;

use Modules\Logistics\Models\DeliveryRate;

class CostCalculationService
{
    public function calculateForStop(string $orgId, array $stopData): float
    {
        $zoneId     = $stopData['zone_id'] ?? null;
        $unitCount  = (int)   ($stopData['unit_count'] ?? 1);
        $weightKg   = (float) ($stopData['weight_kg']  ?? 0);

        // Find zone-specific rate first, fall back to org default
        $rate = DeliveryRate::where('org_id', $orgId)
            ->where('is_active', true)
            ->where(function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId)->orWhereNull('zone_id');
            })
            ->orderByRaw('CASE WHEN zone_id IS NOT NULL THEN 0 ELSE 1 END')
            ->first();

        if (! $rate) return 0.0;

        return $rate->calculate($unitCount, $weightKg);
    }

    public function previewCost(string $orgId, string $zoneId, int $unitCount, float $weightKg): array
    {
        $rate = DeliveryRate::where('org_id', $orgId)
            ->where('zone_id', $zoneId)
            ->where('is_active', true)
            ->first();

        if (! $rate) {
            $rate = DeliveryRate::where('org_id', $orgId)
                ->whereNull('zone_id')
                ->where('is_active', true)
                ->first();
        }

        if (! $rate) {
            return ['cost' => 0, 'rate_id' => null, 'breakdown' => null];
        }

        $cost = $rate->calculate($unitCount, $weightKg);

        return [
            'cost'      => $cost,
            'currency'  => $rate->currency,
            'rate_id'   => $rate->id,
            'rate_name' => $rate->name,
            'breakdown' => [
                'base'        => (float) $rate->base_rate,
                'units'       => $unitCount * (float) $rate->rate_per_unit,
                'weight'      => $weightKg  * (float) $rate->rate_per_kg,
                'total'       => $cost,
            ],
        ];
    }
}
