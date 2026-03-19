<?php

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends CommerceModel
{
    protected $table    = 'product_attributes';
    protected $fillable = ['variant_id', 'key', 'value'];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
