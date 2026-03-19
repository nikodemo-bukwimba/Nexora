<?php

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends CommerceModel
{
    protected $table    = 'order_items';
    protected $fillable = [
        'order_id', 'variant_id', 'product_id',
        'product_name', 'variant_name', 'sku',
        'quantity', 'unit_price', 'subtotal',
        'discount_amount', 'total', 'currency', 'reservation_id',
    ];

    protected function casts(): array
    {
        return [
            'unit_price'      => 'decimal:4',
            'subtotal'        => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'total'           => 'decimal:4',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
