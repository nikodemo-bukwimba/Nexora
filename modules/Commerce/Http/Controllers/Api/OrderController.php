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
        /**
     * Admin direct order creation — bypasses basket buyer/seller check.
     * Used by the admin dashboard to create orders on behalf of customers.
     */
    public function adminStore(Request $request, string $orgId): JsonResponse
    {
        $validated = $request->validate([
            'buyer_id'           => ['required', 'string'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'string', 'exists:commerce.product_variants,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'payment_ref'        => ['nullable', 'string'],
            'currency'           => ['nullable', 'string', 'size:3'],
        ]);

        $sellerActorId = $request->user()->actor_id;

        // ── Resolve root org and full tree org IDs ──────────────
        $org       = \Modules\Platform\Models\Organization::findOrFail($orgId);
        $rootOrgId = $org->root_org_id ?? $orgId;

        $treeOrgIds = app(\Modules\Platform\Contracts\Services\OrgScopeResolverInterface::class)
            ->scopeIds($rootOrgId);

        // ── Validate customer belongs to any node in the tree ───
        $customer = \Modules\PharmaMarketing\Models\Customer::whereIn('org_id', $treeOrgIds)
            ->where('id', $validated['buyer_id'])
            ->firstOrFail();

        // ── Resolve buyer actor ID ───────────────────────────────
        $buyerActorId = null;
        if ($customer->platform_user_id) {
            $platformUser = \Modules\Platform\Models\User::find($customer->platform_user_id);
            $buyerActorId = $platformUser?->actor_id;
        }
        $buyerActorId = $buyerActorId ?? $customer->id;

        $orderItems = [];
        $subtotal   = 0;

        foreach ($validated['items'] as $item) {
            $variant = \Modules\Commerce\Models\ProductVariant::findOrFail($item['variant_id']);

            // ── Validate variant belongs to any node in the tree ─
            if (! in_array($variant->product->org_id, $treeOrgIds)) {
                abort(403, 'Variant does not belong to your organisation.');
            }

            $lineTotal  = $variant->base_price * $item['quantity'];
            $subtotal  += $lineTotal;

            $orderItems[] = [
                'variant_id'   => $variant->id,
                'product_id'   => $variant->product_id,
                'product_name' => $variant->product->name ?? '',
                'variant_name' => $variant->name,
                'quantity'     => $item['quantity'],
                'unit_price'   => $variant->base_price,
                'subtotal'     => $lineTotal,
                'total'        => $lineTotal,
                'currency'     => $validated['currency'] ?? 'TZS',
            ];
        }

        $order = \DB::connection('commerce')->transaction(function () use (
            $validated, $orgId, $rootOrgId, $sellerActorId, $buyerActorId, $customer, $orderItems, $subtotal
        ) {
            $order = \Modules\Commerce\Models\Order::create([
                'seller_org_id'   => $orgId,
                'buyer_org_id'    => $customer->org_id,
                'buyer_actor_id'  => $buyerActorId,
                'seller_actor_id' => $sellerActorId,
                'status'          => 'confirmed',
                'subtotal'        => $subtotal,
                'total'           => $subtotal,
                'currency'        => $validated['currency'] ?? 'TZS',
                'payment_ref'     => $validated['payment_ref'] ?? null,
                'order_number'    => $this->generateOrderNumber(),
                'metadata'        => [
                    'customer_id'   => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_code' => $customer->code,
                    'placed_by'     => 'admin',
                ],
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            return $order;
        });

        return response()->json([
            'message' => 'Order created.',
            'order'   => $order->load('items'),
        ], 201);
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
    public function markPaid(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'payment_status'      => ['required', 'string', 'in:paid,unpaid,partial,refunded'],
            'payment_verified_by' => ['nullable', 'string'],
            'payment_verified_at' => ['nullable', 'string'],
            'payment_ref'         => ['nullable', 'string'],
        ]);

        $order = \Modules\Commerce\Models\Order::findOrFail($id);

        // Store payment audit fields in metadata
        $meta = $order->metadata ?? [];
        $meta['payment_status']      = $validated['payment_status'];
        $meta['payment_verified_by'] = $validated['payment_verified_by'] ?? null;
        $meta['payment_verified_at'] = $validated['payment_verified_at'] ?? now()->toISOString();
        if (isset($validated['payment_ref'])) {
            $meta['payment_ref'] = $validated['payment_ref'];
        }

        $order->update(['metadata' => $meta]);

        return response()->json([
            'message' => 'Payment status updated.',
            'order'   => $order->fresh(),
        ]);
    }
}
