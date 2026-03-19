<?php

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBundle extends CommerceModel
{
    protected $table    = 'product_bundles';
    protected $fillable = ['bundle_product_id', 'component_variant_id', 'quantity'];

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'bundle_product_id');
    }

    public function componentVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'component_variant_id');
    }
}
