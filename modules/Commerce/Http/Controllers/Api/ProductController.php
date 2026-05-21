<?php

namespace Modules\Commerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Commerce\Models\Product;
use Modules\Commerce\Models\ProductVariant;
use Modules\Commerce\Services\ProductService;
use Modules\PharmaMarketing\Services\PromotionPricingService;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService          $products,
        protected PromotionPricingService $pricing,
    ) {}

    /** GET /api/v1/commerce/orgs/{orgId}/products */
    public function index(Request $request, string $orgId): JsonResponse
    {
        $paginator = $this->products->listForOrg(
            $orgId,
            $request->only(['status', 'type', 'search']),
            (int) $request->get('per_page', 25)
        );

        // Root org promotions → all branches see them.
        // Branch own promotions → only that branch sees them.
        // Other branches' promotions → excluded.
        $rootOrgId = \Modules\Platform\Models\Organization::find($orgId)?->root_org_id ?? $orgId;
        $orgIdsForPricing = collect([$rootOrgId, $orgId])->unique()->values()->all();

        $this->decoratePaginator($orgIdsForPricing, $paginator);

        return response()->json($paginator);
    }

    /** GET /api/v1/commerce/products/{id} */
    public function show(Request $request, string $id): JsonResponse
    {
        $product = $this->products->get($id);

        // Use requesting org from query param to include branch-specific promotions.
        // Falls back to $product->org_id (root) when not provided.
        $requestingOrgId = $request->query('org_id', $product->org_id);

        $this->decorateProduct($requestingOrgId, $product);
        return response()->json($product);
    }

    /** POST /api/v1/commerce/orgs/{orgId}/products */
    public function store(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'type'                  => ['sometimes', 'string', 'in:physical,service,digital,bundle'],
            'seller_actor_id'       => ['sometimes', 'nullable', 'string', 'size:26'],
            'variants'              => ['sometimes', 'array', 'min:1'],
            'variants.*.base_price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.currency'   => ['required_with:variants', 'string', 'size:3'],
        ]);

        $sellerActorId = $request->seller_actor_id;
        if (empty($sellerActorId)) {
            $org           = \Modules\Platform\Models\Organization::findOrFail($orgId);
            $sellerActorId = $org->actor_id;
        }

        logger()->info('PRODUCT STORE REQUEST', $request->all());

        $product = $this->products->create(
            $orgId,
            $sellerActorId,
            $request->all()
        );        
        return response()->json(['message' => 'Product created.', 'product' => $product], 201);
    }

    /** PATCH /api/v1/commerce/products/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update($request->only([
            'name', 'description', 'requires_confirmation',
            'track_inventory', 'media', 'attributes', 'metadata',
        ]));
        return response()->json(['message' => 'Product updated.', 'product' => $product->fresh()]);
    }

    /** POST /api/v1/commerce/products/{id}/publish */
    public function publish(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Product published.',
            'product' => $this->products->publish($id),
        ]);
    }

    /** POST /api/v1/commerce/products/{id}/archive */
    public function archive(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Product archived.',
            'product' => $this->products->archive($id),
        ]);
    }

    // ── Pricing injection helpers ──────────────────────────────

    /**
     * Decorate all variants across an entire paginator in one batch query.
     * Collects every variant from every product, runs a single pricing
     * resolution, then writes results back onto each variant model instance.
     */
    private function decoratePaginator(
        array|string $orgIds,
        \Illuminate\Pagination\LengthAwarePaginator $paginator
    ): void {
        $allVariants = $paginator->getCollection()
            ->flatMap(fn(Product $p) => $p->variants);

        if ($allVariants->isEmpty()) {
            return;
        }

        $this->pricing->decorateVariants($orgIds, $allVariants);
    }

    private function decorateProduct(string $orgId, Product $product): void
    {
        if ($product->variants->isEmpty()) {
            return;
        }

        $rootOrgId = \Modules\Platform\Models\Organization::find($orgId)?->root_org_id ?? $orgId;
        $orgIdsForPricing = collect([$rootOrgId, $orgId])->unique()->values()->all();

        $this->pricing->decorateVariants($orgIdsForPricing, $product->variants);
    }

    /** PATCH /api/v1/commerce/variants/{variantId} */
public function updateVariant(Request $request, string $variantId): JsonResponse
{
    $request->validate([
        'base_price' => ['sometimes', 'numeric', 'min:0'],
        'currency'   => ['sometimes', 'string', 'size:3'],
        'is_active'  => ['sometimes', 'boolean'],
        'sku'        => ['sometimes', 'nullable', 'string', 'max:100'],
    ]);

    $variant = ProductVariant::findOrFail($variantId);
    $variant->update($request->only(['base_price', 'currency', 'is_active', 'sku']));

    return response()->json([
        'message' => 'Variant updated.',
        'variant' => $variant->fresh(),
    ]);
}
}