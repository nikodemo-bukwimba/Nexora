<?php

namespace Modules\PharmaMarketing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PharmaMarketing\Models\ProductUpdate;
use Modules\PharmaMarketing\Models\PromotionProductOverride;

/**
 * Manages per-variant discount overrides for a promotion.
 *
 * When a promotion has a flat discount_percentage (e.g. 20%), the admin
 * can use this endpoint to override individual variants with a different
 * percentage — or null to explicitly fall back to the promotion default.
 *
 * Base: /api/v1/pharma/product-updates/{id}/overrides
 */
class PromotionOverrideController extends Controller
{
    /**
     * GET /api/v1/pharma/product-updates/{id}/overrides
     * Returns the promotion-level discount and all per-variant overrides.
     */
    public function index(string $id): JsonResponse
    {
        $update    = ProductUpdate::findOrFail($id);
        $overrides = PromotionProductOverride::where('product_update_id', $id)->get();

        return response()->json([
            'promotion_discount' => $update->discount_percentage,
            'overrides'          => $overrides,
        ]);
    }

    /**
     * PUT /api/v1/pharma/product-updates/{id}/overrides
     * Full replace (sync) of per-variant overrides.
     *
     * Request body:
     * {
     *   "overrides": [
     *     { "variant_id": "01J...", "discount_percentage": 25.00 },
     *     { "variant_id": "01J...", "discount_percentage": null }
     *   ]
     * }
     *
     * null discount_percentage = use the promotion-level default for that variant.
     * Omitting a variant entirely also means it uses the promotion default.
     */
    public function sync(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'overrides'                       => ['required', 'array'],
            'overrides.*.variant_id'          => ['required', 'string', 'size:26'],
            'overrides.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        ProductUpdate::findOrFail($id);

        // Delete all existing overrides for this promotion then re-insert
        PromotionProductOverride::where('product_update_id', $id)->delete();

        $now     = now();
        $inserts = collect($request->overrides)->map(fn($o) => [
            'product_update_id'   => $id,
            'variant_id'          => $o['variant_id'],
            'discount_percentage' => $o['discount_percentage'] ?? null,
            'created_at'          => $now,
            'updated_at'          => $now,
        ])->all();

        if (!empty($inserts)) {
            PromotionProductOverride::insert($inserts);
        }

        return response()->json([
            'message'   => 'Overrides saved.',
            'overrides' => PromotionProductOverride::where('product_update_id', $id)->get(),
        ]);
    }
}