<?php

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends CommerceModel
{
    protected $table    = 'product_variants';
    protected $fillable = [
        'product_id', 'sku', 'name', 'base_price', 'currency',
        'weight_kg', 'cost_price', 'is_default', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'base_price'  => 'decimal:4',
            'cost_price'  => 'decimal:4',
            'weight_kg'   => 'decimal:4',
            'is_default'  => 'boolean',
            'is_active'   => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class, 'variant_id');
    }
}
