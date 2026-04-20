<?php

namespace Modules\Commerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Commerce\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(protected ProductService $products) {}

    public function index(Request $request, string $orgId): JsonResponse
    {
        return response()->json($this->products->listForOrg($orgId, $request->only(['status', 'type', 'search']), (int) $request->get('per_page', 25)));
    }

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

        // Derive seller_actor_id from the org's actor if client did not send it
        $sellerActorId = $request->seller_actor_id;
        if (empty($sellerActorId)) {
            $org = \Modules\Platform\Models\Organization::findOrFail($orgId);
            $sellerActorId = $org->actor_id;
        }

        $product = $this->products->create($orgId, $sellerActorId, $request->all());
        return response()->json(['message' => 'Product created.', 'product' => $product], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->products->get($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = \Modules\Commerce\Models\Product::findOrFail($id);
        $product->update($request->only(['name', 'description', 'requires_confirmation', 'track_inventory', 'media', 'attributes', 'metadata']));
        return response()->json(['message' => 'Product updated.', 'product' => $product->fresh()]);
    }

    public function publish(string $id): JsonResponse
    {
        return response()->json(['message' => 'Product published.', 'product' => $this->products->publish($id)]);
    }

    public function archive(string $id): JsonResponse
    {
        return response()->json(['message' => 'Product archived.', 'product' => $this->products->archive($id)]);
    }
}
