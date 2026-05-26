<?php

namespace Modules\Commerce\Services;

use Illuminate\Support\Collection;
use Modules\Commerce\Models\BranchVariantPriceOverride;
use Modules\Commerce\Models\ProductVariant;
use Modules\Platform\Models\Organization;

class BranchPricingService
{
    /**
     * Set or update a branch-level price override for a variant.
     *
     * A branch cannot set a price lower than the product's root base_price
     * to protect margin — enforce this rule here.
     */
    public function setOverride(
        string $orgId,
        string $variantId,
        array  $data,
        string $userId
    ): BranchVariantPriceOverride {
        $org = Organization::findOrFail($orgId);

        if ($org->type === 'root') {
            throw new \RuntimeException(
                'Root organizations use the variant base_price directly. ' .
                'Set overrides on branch organizations only.'
            );
        }

        $variant = ProductVariant::findOrFail($variantId);

        if ((float) $data['price'] < (float) $variant->base_price) {
            throw new \InvalidArgumentException(
                "Branch price ({$data['price']}) cannot be lower than the product base price ({$variant->base_price}). " .
                "Use a promotion discount to reduce prices."
            );
        }

        return BranchVariantPriceOverride::updateOrCreate(
            [
                'org_id'     => $orgId,
                'variant_id' => $variantId,
            ],
            [
                'price'      => $data['price'],
                'currency'   => $data['currency'] ?? $variant->currency,
                'created_by' => $userId,
            ]
        );
    }

    /**
     * Remove a branch price override — the branch falls back to root base_price.
     */
    public function removeOverride(string $orgId, string $variantId): void
    {
        BranchVariantPriceOverride::where('org_id', $orgId)
            ->where('variant_id', $variantId)
            ->delete();
    }

    /**
     * List all overrides for a branch org with variant details.
     */
    public function listForOrg(string $orgId): Collection
    {
        return BranchVariantPriceOverride::where('org_id', $orgId)
            ->with(['variant.product'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Fetch branch override prices keyed by variant_id for a given branch.
     * Used by PromotionPricingService to resolve the effective base before
     * applying promotion discounts.
     *
     * @return Collection  keyed by variant_id → { price, currency }
     */
    public function resolveOverrideMap(string $orgId, array $variantIds): Collection
    {
        if (empty($variantIds)) {
            return collect();
        }

        return BranchVariantPriceOverride::where('org_id', $orgId)
            ->whereIn('variant_id', $variantIds)
            ->get()
            ->keyBy('variant_id');
    }
}