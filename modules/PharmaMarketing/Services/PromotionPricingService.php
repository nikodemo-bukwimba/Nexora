<?php

namespace Modules\PharmaMarketing\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Commerce\Models\BranchVariantPriceOverride;
use Modules\Commerce\Models\ProductVariant;

/**
 * Resolves effective (promotion-discounted) prices for product variants.
 *
 * Pricing resolution order (highest priority first):
 *  1. Branch price override (branch_variant_price_overrides)  ← NEW
 *  2. Root variant base_price
 *  3. Active promotion discount applied on top of whichever base was resolved
 *
 * Promotion active conditions:
 *  - status IN ('sending','sent')
 *  - today is between start_date and end_date (inclusive)
 *
 * Per-variant promotion override (pm_promotion_product_overrides) takes
 * precedence over the promotion-level discount_percentage.
 *
 * When multiple active promotions cover the same variant, the highest
 * discount wins.
 */
class PromotionPricingService
{
    /**
     * Decorate a collection of ProductVariant Eloquent models (or plain
     * arrays / stdClass objects) with pricing fields:
     *
     *   branch_price          – branch-specific base price (null = no override)
     *   effective_base_price  – branch_price ?? base_price (before promotions)
     *   effective_price       – after promotion discount applied on effective_base_price
     *   discount_percentage   – promotion discount applied (null = no active promotion)
     *   has_promotion         – bool
     *   promotion_id          – id of winning promotion (null = none)
     *
     * @param  array|string   $orgIds           Org IDs for promotion scoping (root + requesting branch)
     * @param  mixed          $variants         Collection/array of ProductVariant models or arrays
     * @param  string|null    $requestingOrgId  The specific branch whose price overrides to load.
     *                                          When null (root org), no branch overrides are applied.
     */
    public function decorateVariants(
        array|string $orgIds,
        $variants,
        ?string $requestingOrgId = null
    ): Collection {
        $variants = collect($variants);

        if ($variants->isEmpty()) {
            return $variants;
        }

        $variantIds = $variants->map(fn($v) => $v instanceof ProductVariant
            ? $v->id
            : (is_array($v) ? $v['id'] : $v->id)
        )->all();

        // ── 1. Load branch price overrides ────────────────────────────────────
        $branchOverrides = $requestingOrgId
            ? $this->resolveBranchOverrides($requestingOrgId, $variantIds)
            : collect();

        // ── 2. Resolve active promotion discounts ─────────────────────────────
        $activeDiscounts = $this->resolveActiveDiscounts($orgIds, $variantIds);

        // ── 3. Decorate each variant ──────────────────────────────────────────
        return $variants->map(function ($variant) use ($activeDiscounts, $branchOverrides) {
            $isModel   = $variant instanceof ProductVariant;
            $id        = $isModel ? $variant->id        : (is_array($variant) ? $variant['id']         : $variant->id);
            $basePrice = (float) ($isModel ? $variant->base_price : (is_array($variant) ? $variant['base_price'] : $variant->base_price));

            // Branch override takes precedence over root base_price
            $branchOverride  = $branchOverrides->get($id);
            $branchPrice     = $branchOverride ? (float) $branchOverride->price : null;
            $effectiveBase   = $branchPrice ?? $basePrice;

            // Apply promotion discount on top of effective base
            $discount           = $activeDiscounts->get($id);
            $effectivePrice     = $effectiveBase;
            $discountPercentage = null;
            $promotionId        = null;

            if ($discount) {
                $discountPercentage = (float) $discount->discount_percentage;
                $effectivePrice     = round($effectiveBase * (1 - $discountPercentage / 100), 2);
                $promotionId        = $discount->product_update_id;
            }

            $pricing = [
                'branch_price'         => $branchPrice,
                'effective_base_price' => $effectiveBase,
                'effective_price'      => $effectivePrice,
                'discount_percentage'  => $discountPercentage,
                'has_promotion'        => $discount !== null,
                'promotion_id'         => $promotionId,
            ];

            if ($isModel) {
                foreach ($pricing as $key => $value) {
                    $variant->{$key} = $value;
                }
                return $variant;
            }

            return is_array($variant)
                ? array_merge($variant, $pricing)
                : (object) array_merge((array) $variant, $pricing);
        });
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Load branch-level price overrides keyed by variant_id.
     */
    private function resolveBranchOverrides(string $orgId, array $variantIds): Collection
    {
        return BranchVariantPriceOverride::where('org_id', $orgId)
            ->whereIn('variant_id', $variantIds)
            ->get()
            ->keyBy('variant_id');
    }

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
            ->whereIn('org_id', (array) $orgIds)
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

        // Collect all product IDs referenced by these promotions
        $allProductIds = $promotions
            ->flatMap(fn($p) => json_decode($p->product_ids ?? '[]', true))
            ->unique()
            ->values()
            ->all();

        if (empty($allProductIds)) {
            return collect();
        }

        // Resolve variant_id → product_id for our batch of variants
        $variantToProduct = DB::connection('commerce')
            ->table('product_variants')
            ->whereIn('product_id', $allProductIds)
            ->whereIn('id', $variantIds)
            ->pluck('product_id', 'id'); // keyed: variant_id => product_id

        if ($variantToProduct->isEmpty()) {
            return collect();
        }

        // Load per-variant promotion overrides
        $overrides = DB::connection('pharma_marketing')
            ->table('pm_promotion_product_overrides')
            ->whereIn('product_update_id', $promotions->pluck('id'))
            ->whereIn('variant_id', $variantIds)
            ->get()
            ->keyBy('variant_id');

        // For each variant find the best (highest discount) active promotion
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