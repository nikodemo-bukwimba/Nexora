<?php

namespace Modules\Commerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Commerce\Services\InventoryDeductionService;
use Modules\Commerce\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService              $orders,
        protected InventoryDeductionService $inventoryDeduction,
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

        // ── Persist + deduct stock (single transaction) ──────────
        try {
            $order = \DB::connection('commerce')->transaction(function () use (
                $validated, $orgId, $buyerOrgId, $sellerActorId, $buyerActorId,
                $orderItems, $deductionItems, $subtotal, $metadata
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

                // ── Inventory deduction grouped by product org (FEFO) ──
                // Each product's stock lives under its own org_id in the
                // inventory schema — NOT under the commerce seller org.
                $byProductOrg = collect($deductionItems)->groupBy('product_org_id');

                foreach ($byProductOrg as $inventoryOrgId => $items) {
                    $this->inventoryDeduction->deductForOrder(
                        orgId:       $orgId,
                        orderId:     $order->id,
                        performedBy: $sellerActorId,
                        items:       $deductionItems,
                    );
                }
                // ── End inventory deduction ────────────────────────────

                return $order;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

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