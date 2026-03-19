<?php

namespace Modules\Finance\Models;

class OrgPricingTier extends FinanceModel
{
    protected $table    = 'org_pricing_tiers';
    protected $fillable = [
        'org_id', 'name', 'description',
        'discount_percent', 'is_default', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'decimal:4',
            'is_default'       => 'boolean',
            'is_active'        => 'boolean',
        ];
    }
}
