<?php

namespace Modules\Commerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Commerce\Models\Product;
use Modules\Commerce\Models\ProductVariant;
use Modules\Commerce\Services\ProductService;
use Modules\PharmaMarketing\Services\PromotionPricingService;
use Modules\Platform\Models\Organization;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService          $products,
        protected PromotionPricingService $pricing,
    ) {}

    /**
     * GET /api/v1/commerce/orgs/{orgId}/products
     *
     * Root org  → products from the full org tree (all branches).
     * Branch    → products owned by that branch only.
     */
    public function index(Request $request, string $orgId): JsonResponse
    {
        $paginator = $this->products->listForOrg(
            $orgId,
            $request->only(['status', 'type', 'search', 'branch_id']),
            (int) $request->get('per_page', 25)
        );

        $rootOrgId        = Organization::find($orgId)?->root_org_id ?? $orgId;
        $orgIdsForPricing = collect([$rootOrgId, $orgId])->unique()->values()->all();

        $this->decoratePaginator($orgIdsForPricing, $paginator, $orgId);

        return response()->json($paginator);
    }

    /** GET /api/v1/commerce/products/{id} */
    public function show(Request $request, string $id): JsonResponse
    {
        $product = $this->products->get($id);

        $requestingOrgId  = $request->query('org_id', $product->org_id);
        $rootOrgId        = Organization::find($requestingOrgId)?->root_org_id ?? $requestingOrgId;
        $orgIdsForPricing = collect([$rootOrgId, $requestingOrgId])->unique()->values()->all();

        $this->decorateProduct($orgIdsForPricing, $requestingOrgId, $product);

        return response()->json($product);
    }

    /**
     * POST /api/v1/commerce/orgs/{orgId}/products
     *
     * Products are created at the supplied $orgId (root or branch).
     * The $orgId in the route IS the owning org — do not force to root.
     */
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
            $org           = Organization::findOrFail($orgId);
            $sellerActorId = $org->actor_id;
        }

        $product = $this->products->create($orgId, $sellerActorId, $request->all());

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

    // ── Pricing decoration helpers ─────────────────────────────────────────────

    private function decoratePaginator(
        array  $orgIdsForPricing,
        \Illuminate\Pagination\LengthAwarePaginator $paginator,
        string $requestingOrgId
    ): void {
        $allVariants = $paginator->getCollection()
            ->flatMap(fn(Product $p) => $p->variants);

        if ($allVariants->isEmpty()) {
            return;
        }

        $this->pricing->decorateVariants($orgIdsForPricing, $allVariants, $requestingOrgId);
    }

    private function decorateProduct(
        array   $orgIdsForPricing,
        string  $requestingOrgId,
        Product $product
    ): void {
        if ($product->variants->isEmpty()) {
            return;
        }

        $this->pricing->decorateVariants($orgIdsForPricing, $product->variants, $requestingOrgId);
    }
}