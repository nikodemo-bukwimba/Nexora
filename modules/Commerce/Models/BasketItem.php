<?php

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasketItem extends CommerceModel
{
    protected $table    = 'basket_items';
    protected $fillable = [
        'basket_id', 'variant_id', 'seller_actor_id', 'quantity', 'unit_price', 'currency',
    ];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:4'];
    }

    public function basket(): BelongsTo
    {
        return $this->belongsTo(Basket::class, 'basket_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function lineTotal(): float
    {
        return round($this->quantity * $this->unit_price, 4);
    }
}
