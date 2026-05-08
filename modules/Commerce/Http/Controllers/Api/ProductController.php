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

        // Decorate all variants across all products in one batch query
        $this->decoratePaginator($orgId, $paginator);

        return response()->json($paginator);
    }

    /** GET /api/v1/commerce/products/{id} */
    public function show(string $id): JsonResponse
    {
        $product = $this->products->get($id);
        $this->decorateProduct($product->org_id, $product);
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

    // ── Pricing injection helpers ──────────────────────────────

    /**
     * Decorate all variants across an entire paginator in one batch query.
     * Collects every variant from every product, runs a single pricing
     * resolution, then writes results back onto each variant model instance.
     */
    private function decoratePaginator(
        string $orgId,
        \Illuminate\Pagination\LengthAwarePaginator $paginator
    ): void {
        $allVariants = $paginator->getCollection()
            ->flatMap(fn(Product $p) => $p->variants);

        if ($allVariants->isEmpty()) {
            return;
        }

        // decorateVariants sets virtual attributes in-place on Eloquent models
        $this->pricing->decorateVariants($orgId, $allVariants);
    }

    /**
     * Decorate variants of a single Product model in-place.
     */
    private function decorateProduct(string $orgId, Product $product): void
    {
        if ($product->variants->isEmpty()) {
            return;
        }

        $this->pricing->decorateVariants($orgId, $product->variants);
    }
}