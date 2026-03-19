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
            'type'                  => ['required', 'string', 'in:physical,service,digital,bundle'],
            'seller_actor_id'       => ['required', 'string', 'size:26'],
            'variants'              => ['required', 'array', 'min:1'],
            'variants.*.base_price' => ['required', 'numeric', 'min:0'],
            'variants.*.currency'   => ['required', 'string', 'size:3'],
        ]);

        $product = $this->products->create($orgId, $request->seller_actor_id, $request->all());
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
