<?php

namespace Modules\Commerce\Services;

use Illuminate\Support\Facades\DB;
use Modules\Commerce\Models\Basket;
use Modules\Commerce\Models\BasketItem;
use Modules\Commerce\Models\ProductVariant;

class BasketService
{
    public function getOrCreate(string $buyerActorId): Basket
    {
        return Basket::firstOrCreate(
            ['buyer_actor_id' => $buyerActorId, 'status' => 'active'],
            ['buyer_actor_id' => $buyerActorId, 'status' => 'active']
        );
    }

    public function getWithItems(string $buyerActorId): Basket
    {
        $basket = $this->getOrCreate($buyerActorId);
        return $basket->load(['items.variant.product', 'items.variant.attributes']);
    }

    public function addItem(string $buyerActorId, string $variantId, int $quantity): BasketItem
    {
        $basket  = $this->getOrCreate($buyerActorId);
        $variant = ProductVariant::with('product')->findOrFail($variantId);

        if (! $variant->product->isActive()) {
            throw new \RuntimeException('Product is not available.');
        }

        $existing = BasketItem::where('basket_id', $basket->id)
            ->where('variant_id', $variantId)
            ->first();

        if ($existing) {
            $existing->update(['quantity' => $existing->quantity + $quantity]);
            return $existing->fresh();
        }

        return BasketItem::create([
            'basket_id'       => $basket->id,
            'variant_id'      => $variantId,
            'seller_actor_id' => $variant->product->seller_actor_id,
            'quantity'        => $quantity,
            'unit_price'      => $variant->base_price,
            'currency'        => $variant->currency,
        ]);
    }

    public function updateItem(string $buyerActorId, string $variantId, int $quantity): BasketItem
    {
        $basket = $this->getOrCreate($buyerActorId);
        $item   = BasketItem::where('basket_id', $basket->id)
            ->where('variant_id', $variantId)
            ->firstOrFail();

        if ($quantity <= 0) {
            $item->delete();
            return $item;
        }

        $item->update(['quantity' => $quantity]);
        return $item->fresh();
    }

    public function removeItem(string $buyerActorId, string $variantId): void
    {
        $basket = $this->getOrCreate($buyerActorId);
        BasketItem::where('basket_id', $basket->id)
            ->where('variant_id', $variantId)
            ->delete();
    }

    public function clear(string $buyerActorId): void
    {
        $basket = Basket::where('buyer_actor_id', $buyerActorId)->where('status', 'active')->first();
        if ($basket) {
            $basket->items()->delete();
        }
    }
}
