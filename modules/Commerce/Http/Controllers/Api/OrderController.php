<?php

namespace Modules\Commerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Commerce\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function show(string $id): JsonResponse
    {
        return response()->json($this->orders->get($id));
    }

    public function forBuyer(Request $request, string $actorId): JsonResponse
    {
        return response()->json($this->orders->listForBuyer($actorId, $request->only(['status']), (int) $request->get('per_page', 25)));
    }

    public function forSeller(Request $request, string $orgId): JsonResponse
    {
        return response()->json($this->orders->listForSeller($orgId, $request->only(['status']), (int) $request->get('per_page', 25)));
    }

    public function confirm(string $id): JsonResponse
    {
        return response()->json(['message' => 'Order confirmed.', 'order' => $this->orders->confirm($id)]);
    }

    public function markProcessing(string $id): JsonResponse
    {
        return response()->json(['message' => 'Order processing.', 'order' => $this->orders->markProcessing($id)]);
    }

    public function ship(Request $request, string $id): JsonResponse
    {
        $request->validate(['carrier' => ['nullable', 'string'], 'tracking_number' => ['nullable', 'string']]);
        return response()->json(['message' => 'Order shipped.', 'order' => $this->orders->ship($id, $request->all())]);
    }

    public function deliver(string $id): JsonResponse
    {
        return response()->json(['message' => 'Order delivered.', 'order' => $this->orders->deliver($id)]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Order cancelled.', 'order' => $this->orders->cancel($id, $request->user()->actor_id)]);
    }

    public function requestReturn(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'min:10']]);
        $return = $this->orders->requestReturn($id, $request->user()->actor_id, $request->reason);
        return response()->json(['message' => 'Return requested.', 'return' => $return], 201);
    }

    public function approveReturn(Request $request, string $id, string $returnId): JsonResponse
    {
        $request->validate(['resolution' => ['required', 'string', 'in:refund,replacement,store_credit']]);
        $return = $this->orders->approveReturn($returnId, $request->user()->actor_id, $request->resolution, $request->refund_amount ?? null);
        return response()->json(['message' => 'Return approved.', 'return' => $return]);
    }
}
