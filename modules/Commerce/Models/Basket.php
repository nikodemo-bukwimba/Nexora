<?php

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Basket extends CommerceModel
{
    protected $fillable = ['buyer_actor_id', 'status', 'promotion_code'];

    public function items(): HasMany
    {
        return $this->hasMany(BasketItem::class, 'basket_id');
    }

    public function isActive(): bool { return $this->status === 'active'; }

    /** Group items by seller for checkout split */
    public function groupedBySeller(): \Illuminate\Support\Collection
    {
        return $this->items->groupBy('seller_actor_id');
    }
}
