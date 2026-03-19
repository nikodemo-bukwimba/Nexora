<?php

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends CommerceModel
{
    protected $fillable = [
        'order_number', 'basket_id', 'buyer_actor_id', 'seller_actor_id',
        'buyer_org_id', 'seller_org_id', 'invoice_id', 'payment_id',
        'status', 'subtotal', 'shipping_amount', 'tax_amount',
        'discount_amount', 'total', 'currency',
        'shipping_rate_id', 'shipping_address', 'billing_address',
        'notes', 'metadata', 'confirmed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'        => 'decimal:4',
            'shipping_amount' => 'decimal:4',
            'tax_amount'      => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'total'           => 'decimal:4',
            'shipping_address' => 'array',
            'billing_address'  => 'array',
            'metadata'         => 'array',
            'confirmed_at'     => 'datetime',
            'cancelled_at'     => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function fulfillment(): HasOne
    {
        return $this->hasOne(OrderFulfillment::class, 'order_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class, 'order_id');
    }

    public function isPending(): bool     { return $this->status === 'pending'; }
    public function isConfirmed(): bool   { return $this->status === 'confirmed'; }
    public function isDelivered(): bool   { return $this->status === 'delivered'; }
    public function isCancelled(): bool   { return $this->status === 'cancelled'; }
    public function isDisputed(): bool    { return $this->status === 'disputed'; }
}
