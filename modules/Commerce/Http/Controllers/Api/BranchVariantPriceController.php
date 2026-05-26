<?php

namespace Modules\Commerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Commerce\Services\BranchPricingService;

/**
 * Manages branch-level price overrides for product variants.
 *
 * Rationale: Products are catalogued at root org level with a canonical
 * base_price. Branches that add transport costs or regional margins can
 * set a higher price that will be shown to customers/officers in that branch.
 * Promotions are then applied on top of the branch price (not the root price).
 *
 * Base: /api/v1/commerce/orgs/{orgId}/branch-prices
 */
class BranchVariantPriceController extends Controller
{
    public function __construct(
        protected BranchPricingService $branchPricing
    ) {}

    /**
     * GET /api/v1/commerce/orgs/{orgId}/branch-prices
     *
     * List all variant price overrides set by this branch.
     */
    public function index(string $orgId): JsonResponse
    {
        $overrides = $this->branchPricing->listForOrg($orgId);

        return response()->json([
            'data' => $overrides,
        ]);
    }

    /**
     * PUT /api/v1/commerce/orgs/{orgId}/variants/{variantId}/price
     *
     * Create or replace the branch override price for one variant.
     * The price must be >= the variant's root base_price.
     *
     * Request body:
     * {
     *   "price": 4500.00,
     *   "currency": "TZS"   // optional — inherits variant currency if omitted
     * }
     */
    public function upsert(Request $request, string $orgId, string $variantId): JsonResponse
    {
        $request->validate([
            'price'    => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $override = $this->branchPricing->setOverride(
            $orgId,
            $variantId,
            $request->only(['price', 'currency']),
            $request->user()->id
        );

        return response()->json([
            'message'  => 'Branch price override saved.',
            'override' => $override->fresh(['variant']),
        ], 200);
    }

    /**
     * DELETE /api/v1/commerce/orgs/{orgId}/variants/{variantId}/price
     *
     * Remove the branch override — the branch falls back to root base_price.
     */
    public function destroy(string $orgId, string $variantId): JsonResponse
    {
        $this->branchPricing->removeOverride($orgId, $variantId);

        return response()->json([
            'message' => 'Branch price override removed. Variant will now use root base price.',
        ]);
    }
}