<?php

namespace Modules\Commerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Commerce\Services\BasketService;
use Modules\Commerce\Services\OrderService;

class BasketController extends Controller
{
    public function __construct(
        protected BasketService $baskets,
        protected OrderService  $orders,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $basket = $this->baskets->getWithItems($request->user()->actor_id);
        return response()->json($basket);
    }

    public function addItem(Request $request): JsonResponse
    {
        $request->validate([
            'variant_id' => ['required', 'string', 'size:26'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ]);
        $item = $this->baskets->addItem($request->user()->actor_id, $request->variant_id, $request->quantity);
        return response()->json(['message' => 'Item added.', 'item' => $item], 201);
    }

    public function updateItem(Request $request, string $variantId): JsonResponse
    {
        $request->validate(['quantity' => ['required', 'integer', 'min:0']]);
        $item = $this->baskets->updateItem($request->user()->actor_id, $variantId, $request->quantity);
        return response()->json(['message' => 'Item updated.', 'item' => $item]);
    }

    public function removeItem(Request $request, string $variantId): JsonResponse
    {
        $this->baskets->removeItem($request->user()->actor_id, $variantId);
        return response()->json(['message' => 'Item removed.']);
    }

    public function checkout(Request $request): JsonResponse
    {
        $createdOrders = $this->orders->checkout($request->user()->actor_id, $request->all());
        return response()->json([
            'message' => count($createdOrders) . ' order(s) created.',
            'orders'  => $createdOrders,
        ], 201);
    }
}
