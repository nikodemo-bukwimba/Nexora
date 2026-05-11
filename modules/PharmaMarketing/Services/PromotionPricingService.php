<?php

namespace Modules\PharmaMarketing\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Commerce\Models\ProductVariant;

/**
 * Resolves effective (promotion-discounted) prices for product variants.
 *
 * Rules:
 *  - A promotion is "active" when: status IN ('sending','sent')
 *    AND today is between start_date and end_date (inclusive).
 *  - Per-variant override (pm_promotion_product_overrides) takes precedence
 *    over the promotion-level discount_percentage.
 *  - When multiple active promotions cover the same variant, the highest
 *    discount wins.
 *  - base_price is NEVER mutated — effective_price is computed on the fly.
 */
class PromotionPricingService
{
    /**
     * Decorate a collection of ProductVariant Eloquent models (or plain
     * arrays / stdClass objects) with pricing fields:
     *   effective_price, discount_percentage, has_promotion, promotion_id
     *
     * For Eloquent models the fields are set as virtual attributes in-place.
     * For arrays/objects a decorated copy is returned.
     */
    public function decorateVariants(array|string $orgIds, $variants): Collection
    {
        $variants = collect($variants);

        if ($variants->isEmpty()) {
            return $variants;
        }

        $variantIds = $variants->map(fn($v) => $v instanceof ProductVariant
            ? $v->id
            : (is_array($v) ? $v['id'] : $v->id)
        )->all();

        $activeDiscounts = $this->resolveActiveDiscounts($orgIds, $variantIds);

        return $variants->map(function ($variant) use ($activeDiscounts) {
            $isModel   = $variant instanceof ProductVariant;
            $id        = $isModel ? $variant->id        : (is_array($variant) ? $variant['id']         : $variant->id);
            $basePrice = (float) ($isModel ? $variant->base_price : (is_array($variant) ? $variant['base_price'] : $variant->base_price));

            $discount           = $activeDiscounts->get($id);
            $effectivePrice     = $basePrice;
            $discountPercentage = null;
            $promotionId        = null;

            if ($discount) {
                $discountPercentage = (float) $discount->discount_percentage;
                $effectivePrice     = round($basePrice * (1 - $discountPercentage / 100), 2);
                $promotionId        = $discount->product_update_id;
            }

            if ($isModel) {
                // Set virtual attributes directly on the Eloquent model
                $variant->effective_price     = $effectivePrice;
                $variant->discount_percentage = $discountPercentage;
                $variant->has_promotion       = $discount !== null;
                $variant->promotion_id        = $promotionId;
                return $variant;
            }

            // Plain array or stdObject — merge and return
            $pricing = [
                'base_price'          => $basePrice,
                'effective_price'     => $effectivePrice,
                'discount_percentage' => $discountPercentage,
                'promotion_id'        => $promotionId,
                'has_promotion'       => $discount !== null,
            ];

            return is_array($variant)
                ? array_merge($variant, $pricing)
                : (object) array_merge((array) $variant, $pricing);
        });
    }

    // ── Private helpers ────────────────────────────────────────

    /**
     * Returns a Collection keyed by variant_id, each value being an object
     * with { product_update_id, discount_percentage } representing the best
     * (highest discount) active promotion for that variant.
     */
    private function resolveActiveDiscounts(array|string $orgIds, array $variantIds): Collection
    {
        $today = now()->toDateString();

        $promotions = DB::connection('pharma_marketing')
            ->table('pm_product_updates')
            ->whereIn('org_id', (array) $orgIds)  // ← only this line changes
            ->whereIn('status', ['sending', 'sent'])
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->whereNotNull('discount_percentage')
            ->get(['id', 'discount_percentage', 'product_ids']);

        if ($promotions->isEmpty()) {
            return collect();
        }

        // Step 2: collect all product IDs referenced by these promotions
        $allProductIds = $promotions
            ->flatMap(fn($p) => json_decode($p->product_ids ?? '[]', true))
            ->unique()
            ->values()
            ->all();

        if (empty($allProductIds)) {
            return collect();
        }

        // Step 3: resolve variant_id → product_id for our batch of variants
        $variantToProduct = DB::connection('commerce')
            ->table('product_variants')
            ->whereIn('product_id', $allProductIds)
            ->whereIn('id', $variantIds)
            ->pluck('product_id', 'id'); // keyed: variant_id => product_id

        if ($variantToProduct->isEmpty()) {
            return collect();
        }

        // Step 4: load per-variant overrides for these promotions
        $overrides = DB::connection('pharma_marketing')
            ->table('pm_promotion_product_overrides')
            ->whereIn('product_update_id', $promotions->pluck('id'))
            ->whereIn('variant_id', $variantIds)
            ->get()
            ->keyBy('variant_id');

        // Step 5: for each variant find the best (highest) active discount
        $best = collect();

        foreach ($variantToProduct as $variantId => $productId) {
            $bestPercentage = 0.0;
            $bestDiscount   = null;

            foreach ($promotions as $promotion) {
                $promotedProducts = json_decode($promotion->product_ids ?? '[]', true);

                if (!in_array($productId, $promotedProducts, true)) {
                    continue;
                }

                // Per-variant override takes precedence over promotion default
                $override   = $overrides->get($variantId);
                $percentage = $override
                    ? (float) ($override->discount_percentage ?? $promotion->discount_percentage)
                    : (float) $promotion->discount_percentage;

                if ($percentage > $bestPercentage) {
                    $bestPercentage = $percentage;
                    $bestDiscount   = (object) [
                        'product_update_id'   => $promotion->id,
                        'discount_percentage' => $percentage,
                    ];
                }
            }

            if ($bestDiscount) {
                $best->put($variantId, $bestDiscount);
            }
        }

        return $best;
    }
}