<?php

namespace Modules\Logistics\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Logistics\Services\DriverLocationService;
use Modules\Logistics\Services\OrderTrackingService;

/**
 * Customer-facing tracking endpoints.
 * Customers can only see their own orders (buyer_actor_id check enforced).
 */
class CustomerTrackingController extends Controller
{
    public function __construct(
        protected OrderTrackingService  $tracking,
        protected DriverLocationService $locations,
    ) {}

    /**
     * GET /api/v1/logistics/customer/track/{orderId}
     *
     * Full tracking info for an order the customer owns.
     * Includes stop status, driver info, and ETA.
     *
     * Security: verifies the authenticated actor is the order's buyer_actor_id.
     */
    public function track(Request $request, string $orderId): JsonResponse
    {
        // Ownership check — customer may only track their own orders
        $this->assertOrderOwnership($request, $orderId);

        $tracking = $this->tracking->getTrackingForOrder($orderId);

        return response()->json($tracking);
    }

    /**
     * GET /api/v1/logistics/customer/track/{orderId}/driver-location
     *
     * Returns the driver's current GPS position if the order is actively en_route/arrived.
     * Returns null when driver is not yet dispatched, already delivered, or location is stale.
     *
     * Security: customer must own the order.
     */
    public function driverLocation(Request $request, string $orderId): JsonResponse
    {
        $this->assertOrderOwnership($request, $orderId);

        $position = $this->locations->getPositionForOrder($orderId);

        if (! $position) {
            return response()->json([
                'available' => false,
                'reason'    => 'Driver location is not available. The delivery may not have started yet or the order is already delivered.',
                'position'  => null,
            ]);
        }

        return response()->json([
            'available' => true,
            'position'  => $position,
        ]);
    }

    /**
     * GET /api/v1/logistics/customer/orders
     *
     * List all orders for the authenticated customer actor with their delivery status.
     */
    public function myOrders(Request $request): JsonResponse
    {
        $actorId = $request->user()->actor_id;

        $orders = \DB::connection('commerce')
            ->table('orders as o')
            ->where('o.buyer_actor_id', $actorId)
            ->select([
                'o.id', 'o.order_number', 'o.status', 'o.total',
                'o.currency', 'o.created_at', 'o.confirmed_at',
                'o.metadata',
            ])
            ->orderBy('o.created_at', 'desc')
            ->paginate((int) $request->get('per_page', 20));

        // Enrich each order with its delivery stop status
        $orderIds = collect($orders->items())->pluck('id')->toArray();

        $stopStatuses = \Modules\Logistics\Models\DeliveryStop::whereIn('order_id', $orderIds)
            ->select(['order_id', 'status', 'estimated_arrival_at', 'delivered_at', 'failed_at', 'failure_reason'])
            ->get()
            ->keyBy('order_id');

        $enriched = collect($orders->items())->map(fn($order) => array_merge(
            (array) $order,
            ['delivery' => $stopStatuses->get($order->id)]
        ));

        return response()->json([
            'data'         => $enriched,
            'current_page' => $orders->currentPage(),
            'last_page'    => $orders->lastPage(),
            'total'        => $orders->total(),
            'per_page'     => $orders->perPage(),
        ]);
    }

    /**
     * GET /api/v1/logistics/customer/orders/{orderId}
     *
     * Full order detail + delivery tracking summary for customer.
     */
    public function orderDetail(Request $request, string $orderId): JsonResponse
    {
        $this->assertOrderOwnership($request, $orderId);

        $order = \DB::connection('commerce')
            ->table('orders')
            ->where('id', $orderId)
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $items = \DB::connection('commerce')
            ->table('order_items')
            ->where('order_id', $orderId)
            ->get();

        $tracking = $this->tracking->getTrackingForOrder($orderId);

        return response()->json([
            'order'    => $order,
            'items'    => $items,
            'tracking' => $tracking,
        ]);
    }

    // ── Private helpers ─────────────────────────────────────────

    private function assertOrderOwnership(Request $request, string $orderId): void
    {
        $actorId = $request->user()->actor_id;

        $order = \DB::connection('commerce')
            ->table('orders')
            ->where('id', $orderId)
            ->select(['buyer_actor_id', 'metadata'])
            ->first();

        if (! $order) {
            abort(404, 'Order not found.');
        }

        // Direct actor match
        if ($order->buyer_actor_id === $actorId) return;

        // Fallback: check if the actor's platform user is the customer linked to the order
        $metadata   = is_string($order->metadata) ? json_decode($order->metadata, true) : (array) $order->metadata;
        $customerId = $metadata['customer_id'] ?? null;

        if ($customerId) {
            $platformUserId = \DB::connection('platform')
                ->table('users')
                ->where('actor_id', $actorId)
                ->value('id');

            $customerMatch = \DB::connection('pharma_marketing')
                ->table('pm_customers')
                ->where('id', $customerId)
                ->where('platform_user_id', $platformUserId)
                ->exists();

            if ($customerMatch) return;
        }

        abort(403, 'You do not have access to this order.');
    }
}