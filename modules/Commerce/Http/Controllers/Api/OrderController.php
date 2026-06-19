<?php

namespace Modules\Commerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Commerce\Services\InventoryDeductionService;
use Modules\Commerce\Services\OrderService;
use Modules\Notifications\Services\NotificationService;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService              $orders,
        protected InventoryDeductionService $inventoryDeduction,
        protected NotificationService       $notifications,
    ) {}

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
        return response()->json(
            $this->orders->listForSeller(
                $orgId,
                $request->only(['status', 'branch_id', 'created_by_id']),
                (int) $request->get('per_page', 25)
            )
        );
    }

    public function markProcessing(string $id): JsonResponse
    {
        return response()->json(['message' => 'Order processing.', 'order' => $this->orders->markProcessing($id)]);
    }

    /**
     * Admin direct order creation — bypasses basket buyer/seller check.
     * Used by the admin dashboard to create orders on behalf of customers.
     *
     * Stock is deducted inside the same DB transaction as the order write.
     * Insufficient stock returns 422 and rolls the whole write back.
     */
    public function adminStore(Request $request, string $orgId): JsonResponse
    {
        $validated = $request->validate([
            'buyer_id'                 => ['required', 'string'],
            'buyer_type'               => ['nullable', 'string', 'in:customer,actor'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.variant_id'       => ['required', 'string'],
            'items.*.quantity'         => ['required', 'integer', 'min:1'],
            'items.*.unit_price'       => ['nullable', 'numeric', 'min:0'],
            'payment_ref'              => ['nullable', 'string'],
            'currency'                 => ['nullable', 'string', 'size:3'],
            'notes'                    => ['nullable', 'string'],
            'metadata'                 => ['nullable', 'array'],
            'metadata.created_by_name' => ['nullable', 'string', 'max:255'],
            'metadata.created_by_id'   => ['nullable', 'string', 'max:26'],
        ]);

        $sellerActorId = $request->user()->actor_id;
        $buyerType     = $validated['buyer_type'] ?? 'customer';

        $org       = \Modules\Platform\Models\Organization::findOrFail($orgId);
        $rootOrgId = $org->root_org_id ?? $orgId;

        $treeOrgIds = \Modules\Platform\Models\Organization::where('root_org_id', $rootOrgId)
            ->orWhere('id', $rootOrgId)
            ->pluck('id')
            ->toArray();

        // ── Resolve buyer ────────────────────────────────────────
        $buyerActorId = null;
        $buyerOrgId   = $orgId;
        $customerMeta = [];

        if ($buyerType === 'actor') {
            $actor = \Modules\Platform\Models\Actor::find($validated['buyer_id']);
            if (! $actor) {
                return response()->json(['message' => 'Buyer actor not found.'], 404);
            }
            $buyerActorId = $actor->id;
            $customerMeta = ['buyer_actor_id' => $actor->id, 'buyer_name' => $actor->display_name ?? '', 'placed_by' => 'customer_app'];
        } else {
            $customer = \Modules\PharmaMarketing\Models\Customer::whereIn('org_id', $treeOrgIds)
                ->where('id', $validated['buyer_id'])
                ->first();

            if (! $customer) {
                return response()->json(['message' => 'Customer not found in your organisation.'], 404);
            }

            if ($customer->platform_user_id) {
                $platformUser = \Modules\Platform\Models\User::find($customer->platform_user_id);
                $buyerActorId = $platformUser?->actor_id;
            }
            $buyerActorId = $buyerActorId ?? $customer->id;
            $buyerOrgId   = $customer->org_id;
            $customerMeta = [
                'customer_id'   => $customer->id,
                'customer_name' => $customer->name,
                'customer_code' => $customer->code,
                'placed_by'     => 'admin',
            ];
        }

        // ── Resolve and validate items BEFORE the transaction ────
        $orderItems     = [];
        $deductionItems = [];
        $subtotal       = 0;

        foreach ($validated['items'] as $item) {
            $variant = \Modules\Commerce\Models\ProductVariant::with('product')
                ->find($item['variant_id']);

            if (! $variant) {
                return response()->json([
                    'message' => "Product variant '{$item['variant_id']}' not found.",
                ], 422);
            }

            if (! $variant->product || ! in_array($variant->product->org_id, $treeOrgIds)) {
                return response()->json([
                    'message' => "Variant '{$variant->id}' does not belong to your organisation.",
                ], 403);
            }

            $unitPrice = isset($item['unit_price']) && $item['unit_price'] > 0
                ? (float) $item['unit_price']
                : (float) $variant->base_price;

            $lineTotal  = $unitPrice * $item['quantity'];
            $subtotal  += $lineTotal;

            $orderItems[] = [
                'variant_id'      => $variant->id,
                'product_id'      => $variant->product_id,
                'product_name'    => $variant->product->name ?? '',
                'variant_name'    => $variant->name,
                'quantity'        => $item['quantity'],
                'unit_price'      => $unitPrice,
                'subtotal'        => $lineTotal,
                'total'           => $lineTotal,
                'currency'        => $validated['currency'] ?? 'TZS',
                'discount_amount' => max(0, ((float) $variant->base_price - $unitPrice) * $item['quantity']),
            ];

            $deductionItems[] = [
                'product_id'      => $variant->product_id,
                'product_org_id'  => $variant->product->org_id,
                'variant_id'      => $variant->id,
                'variant_name'    => $variant->name,
                'quantity'        => $item['quantity'],
                'track_inventory' => (bool) $variant->product->track_inventory,
            ];
        }

        // ── Build metadata ────────────────────────────────────────
        $incomingMeta = $request->input('metadata', []);
        $actor        = $request->user();
        $metadata     = array_merge(
            $customerMeta,
            [
                'notes'           => $validated['notes'] ?? null,
                'created_by_name' => $incomingMeta['created_by_name']
                                    ?? $actor?->actor?->display_name
                                    ?? $actor?->username
                                    ?? 'admin',
                'created_by_id'   => $incomingMeta['created_by_id']
                                    ?? $actor?->actor_id,
            ]
        );


    // ── Persist order, reserve stock (single Commerce transaction) ──
        // Reservation only HOLDS stock (quantity_reserved) — it does not
        // touch quantity_available. The actual decrement happens in
        // fulfillReservations() below, AFTER this transaction has
        // committed, which avoids the cross-connection atomicity gap
        // between the 'commerce' and 'inventory' database connections.
        $reservationIds = [];

        try {
            $order = \DB::connection('commerce')->transaction(function () use (
                $validated, $orgId, $buyerOrgId, $sellerActorId, $buyerActorId,
                $orderItems, $deductionItems, $subtotal, $metadata, &$reservationIds
            ) {
                $order = \Modules\Commerce\Models\Order::create([
                    'seller_org_id'   => $orgId,
                    'buyer_org_id'    => $buyerOrgId,
                    'buyer_actor_id'  => $buyerActorId,
                    'seller_actor_id' => $sellerActorId,
                    'status'          => 'confirmed',
                    'subtotal'        => $subtotal,
                    'total'           => $subtotal,
                    'currency'        => $validated['currency'] ?? 'TZS',
                    'payment_ref'     => $validated['payment_ref'] ?? null,
                    'order_number'    => $this->generateOrderNumber(),
                    'metadata'        => $metadata,
                ]);

                foreach ($orderItems as $item) {
                    $order->items()->create($item);
                }

                // Reserve stock for the order. Throws RuntimeException on
                // insufficient stock, which rolls back the Commerce
                // transaction (order/order_items never persist) — no
                // reservation rows survive either, since they're on a
                // different connection but we explicitly release any
                // partial reservations inside reserveForOrder() on failure.
                $reservationIds = $this->inventoryDeduction->reserveForOrder(
                    orgId:   $orgId,
                    orderId: $order->id,
                    items:   $deductionItems,
                );

                return $order;
            });
        } catch (\RuntimeException $e) {
            if (! empty($reservationIds)) {
                $this->inventoryDeduction->releaseReservations($reservationIds);
            }
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            // Non-stock failure (DB error, etc.) after reservations were
            // already made. Release them immediately rather than waiting
            // for the expiry sweep, then re-throw so normal error
            // handling/logging for unexpected exceptions still applies.
            if (! empty($reservationIds)) {
                $this->inventoryDeduction->releaseReservations($reservationIds);
            }
            throw $e;
        }

        // ── Commerce transaction committed — fulfill the reservations ──
        // This is the point of no return: the order definitely exists,
        // so we now actually decrement quantity_available. If this step
        // partially fails, the order still stands (correct — the
        // customer's purchase succeeded) and failures are logged for
        // ops reconciliation rather than surfaced to the customer.
        $this->inventoryDeduction->fulfillReservations($reservationIds);

        if ($buyerActorId) {
            $this->notifications->send(
                actorId:   $buyerActorId,
                type:      'order.placed',
                title:     'Order received',
                body:      "Your order #{$order->order_number} has been placed and is being prepared.",
                refType:   'order',
                refId:     $order->id,
                data:      ['order_number' => $order->order_number, 'total' => $order->total],
            );
        }

        return response()->json([
            'message' => 'Order created.',
            'order'   => $order->load('items'),
        ], 201);
    }

    public function confirm(string $id): JsonResponse
    {
        $order = $this->orders->confirm($id);

        if ($order->buyer_actor_id) {
            $this->notifications->send(
                actorId: $order->buyer_actor_id,
                type:    'order.confirmed',
                title:   'Order confirmed',
                body:    "Your order #{$order->order_number} has been confirmed.",
                refType: 'order',
                refId:   $order->id,
            );
        }

        return response()->json(['message' => 'Order confirmed.', 'order' => $order]);
    }

    public function ship(Request $request, string $id): JsonResponse
    {
        $request->validate(['carrier' => ['nullable', 'string'], 'tracking_number' => ['nullable', 'string']]);
        $order = $this->orders->ship($id, $request->all());

        if ($order->buyer_actor_id) {
            $tracking = $request->tracking_number
                ? " Tracking: {$request->tracking_number}."
                : '';
            $this->notifications->send(
                actorId: $order->buyer_actor_id,
                type:    'order.shipped',
                title:   'Order shipped',
                body:    "Your order #{$order->order_number} is on its way.{$tracking}",
                refType: 'order',
                refId:   $order->id,
                data:    ['tracking_number' => $request->tracking_number, 'carrier' => $request->carrier],
            );
        }

        return response()->json(['message' => 'Order shipped.', 'order' => $order]);
    }

    public function deliver(string $id): JsonResponse
    {
        $order = $this->orders->deliver($id);

        if ($order->buyer_actor_id) {
            $this->notifications->send(
                actorId: $order->buyer_actor_id,
                type:    'order.delivered',
                title:   'Order delivered',
                body:    "Your order #{$order->order_number} has been delivered. Thank you!",
                refType: 'order',
                refId:   $order->id,
            );
        }

        return response()->json(['message' => 'Order delivered.', 'order' => $order]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $order = $this->orders->cancel($id, $request->user()->actor_id);

        if ($order->buyer_actor_id) {
            $this->notifications->send(
                actorId: $order->buyer_actor_id,
                type:    'order.cancelled',
                title:   'Order cancelled',
                body:    "Your order #{$order->order_number} has been cancelled.",
                refType: 'order',
                refId:   $order->id,
            );
        }

        return response()->json(['message' => 'Order cancelled.', 'order' => $order]);
    }

    public function markPaid(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'payment_status'      => ['required', 'string', 'in:paid,unpaid,partial,refunded'],
            'payment_verified_by' => ['nullable', 'string'],
            'payment_verified_at' => ['nullable', 'string'],
            'payment_ref'         => ['nullable', 'string'],
        ]);

        $order = \Modules\Commerce\Models\Order::findOrFail($id);

        $meta = $order->metadata ?? [];
        $meta['payment_status']      = $validated['payment_status'];
        $meta['payment_verified_by'] = $validated['payment_verified_by'] ?? null;
        $meta['payment_verified_at'] = $validated['payment_verified_at'] ?? now()->toISOString();
        if (isset($validated['payment_ref'])) {
            $meta['payment_ref'] = $validated['payment_ref'];
        }
        $order->update(['metadata' => $meta]);

        if ($validated['payment_status'] === 'paid' && $order->buyer_actor_id) {
            $this->notifications->send(
                actorId: $order->buyer_actor_id,
                type:    'payment.confirmed',
                title:   'Payment confirmed',
                body:    "Payment for order #{$order->order_number} has been confirmed.",
                refType: 'order',
                refId:   $order->id,
            );
        }

        return response()->json([
            'message' => 'Payment status updated.',
            'order'   => $order->fresh(),
        ]);
    }

    public function requestReturn(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'min:10']]);
        $return = $this->orders->requestReturn($id, $request->user()->actor_id, $request->reason);

        $order = \Modules\Commerce\Models\Order::find($id);
        if ($order?->buyer_actor_id) {
            $this->notifications->send(
                actorId: $order->buyer_actor_id,
                type:    'order.return_requested',
                title:   'Return request received',
                body:    "Your return request for order #{$order->order_number} has been received and is under review.",
                refType: 'order',
                refId:   $id,
            );
        }

        return response()->json(['message' => 'Return requested.', 'return' => $return], 201);
    }

    public function approveReturn(Request $request, string $id, string $returnId): JsonResponse
    {
        $request->validate(['resolution' => ['required', 'string', 'in:refund,replacement,store_credit']]);
        $return = $this->orders->approveReturn($returnId, $request->user()->actor_id, $request->resolution, $request->refund_amount ?? null);

        $order = \Modules\Commerce\Models\Order::find($id);
        if ($order?->buyer_actor_id) {
            $resolution = match ($request->resolution) {
                'refund'       => 'a refund',
                'replacement'  => 'a replacement',
                'store_credit' => 'store credit',
                default        => $request->resolution,
            };
            $this->notifications->send(
                actorId: $order->buyer_actor_id,
                type:    'order.return_approved',
                title:   'Return approved',
                body:    "Your return for order #{$order->order_number} has been approved. Resolution: {$resolution}.",
                refType: 'order',
                refId:   $id,
            );
        }

        return response()->json(['message' => 'Return approved.', 'return' => $return]);
    }

    private function generateOrderNumber(): string
    {
        $year  = now()->format('Y');
        $count = \DB::connection('commerce')
            ->table('orders')
            ->whereYear('created_at', $year)
            ->count();
        $unique = strtoupper(substr(uniqid(), -4));
        return sprintf('ORD-%s-%06d-%s', $year, $count + 1, $unique);
    }
}