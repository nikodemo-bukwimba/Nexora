<?php

namespace Modules\Commerce\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Platform\Contracts\Services\OrgScopeResolverInterface;
use Modules\Commerce\Models\Basket;
use Modules\Commerce\Models\Order;
use Modules\Commerce\Models\OrderFulfillment;
use Modules\Commerce\Models\OrderItem;
use Modules\Commerce\Models\OrderReturn;

class OrderService
{
    public function __construct(
        protected OrgScopeResolverInterface $scope
    ) {}

    /**
     * Checkout: split basket into one order per seller.
     * Returns array of created orders.
     */
    public function checkout(string $buyerActorId, array $options = []): array
    {
        return DB::connection('commerce')->transaction(function () use ($buyerActorId, $options) {

            $basket = Basket::where('buyer_actor_id', $buyerActorId)
                ->where('status', 'active')
                ->with(['items.variant.product'])
                ->firstOrFail();

            if ($basket->items->isEmpty()) {
                throw new \RuntimeException('Basket is empty.');
            }

            $orders = [];

            foreach ($basket->items->groupBy('seller_actor_id') as $sellerActorId => $items) {
                $subtotal = $items->sum(fn($i) => $i->quantity * $i->unit_price);
                $currency = $items->first()->currency;

                $order = Order::create([
                    'order_number'     => $this->generateOrderNumber(),
                    'basket_id'        => $basket->id,
                    'buyer_actor_id'   => $buyerActorId,
                    'seller_actor_id'  => $sellerActorId,
                    'buyer_org_id'     => $options['buyer_org_id'] ?? null,
                    'seller_org_id'    => $items->first()->variant->product->org_id,
                    'status'           => 'pending',
                    'subtotal'         => $subtotal,
                    'shipping_amount'  => $options['shipping_amount'] ?? 0,
                    'tax_amount'       => 0,
                    'discount_amount'  => 0,
                    'total'            => $subtotal + ($options['shipping_amount'] ?? 0),
                    'currency'         => $currency,
                    'shipping_address' => $options['shipping_address'] ?? null,
                    'billing_address'  => $options['billing_address'] ?? null,
                ]);

                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id'        => $order->id,
                        'variant_id'      => $item->variant_id,
                        'product_id'      => $item->variant->product_id,
                        'product_name'    => $item->variant->product->name,
                        'variant_name'    => $item->variant->name,
                        'sku'             => $item->variant->sku,
                        'quantity'        => $item->quantity,
                        'unit_price'      => $item->unit_price,
                        'subtotal'        => $item->quantity * $item->unit_price,
                        'discount_amount' => 0,
                        'total'           => $item->quantity * $item->unit_price,
                        'currency'        => $item->currency,
                    ]);
                }

                $requiresConfirmation = $items->first()->variant->product->requires_confirmation;
                if (! $requiresConfirmation) {
                    $order->update(['status' => 'confirmed', 'confirmed_at' => now()]);
                }

                $orders[] = $order->fresh(['items']);
            }

            $basket->update(['status' => 'checked_out']);

            return $orders;
        });
    }

    public function get(string $id): Order
    {
        return Order::with(['items.variant', 'fulfillment', 'returns'])->findOrFail($id);
    }

    public function listForBuyer(string $buyerActorId, array $filters, int $perPage): LengthAwarePaginator
    {
        return Order::where('buyer_actor_id', $buyerActorId)
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * List orders for a seller org with tree-awareness.
     *
     * Root admin   → sees orders from ALL branches in the tree
     * Branch user  → sees orders from their branch only
     *
     * Root admin can filter by branch:
     *   $filters['branch_id'] = '01KMQ1...'
     */
    public function listForSeller(string $sellerOrgId, array $filters, int $perPage): LengthAwarePaginator
    {
        $orgIds = $this->scope->scopeIds($sellerOrgId, $filters['branch_id'] ?? null);

        return Order::whereIn('seller_org_id', $orgIds)
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(
                !empty($filters['created_by_id']),
                fn($q) => $q->whereRaw("metadata->>'created_by_id' = ?", [$filters['created_by_id']])
            )
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function confirm(string $orderId): Order
    {
        $order = Order::where('status', 'pending')->findOrFail($orderId);
        $order->update(['status' => 'confirmed', 'confirmed_at' => now()]);
        return $order->fresh();
    }

    public function markProcessing(string $orderId): Order
    {
        $order = Order::where('status', 'confirmed')->findOrFail($orderId);
        $order->update(['status' => 'processing']);
        return $order->fresh();
    }

    public function ship(string $orderId, array $fulfillmentData): Order
    {
        return DB::connection('commerce')->transaction(function () use ($orderId, $fulfillmentData) {
            $order = Order::whereIn('status', ['confirmed', 'processing'])->findOrFail($orderId);

            OrderFulfillment::create(array_merge($fulfillmentData, [
                'order_id'   => $orderId,
                'status'     => 'shipped',
                'shipped_at' => now(),
            ]));

            $order->update(['status' => 'shipped']);
            return $order->fresh(['fulfillment']);
        });
    }

    public function deliver(string $orderId): Order
    {
        $order = Order::where('status', 'shipped')->findOrFail($orderId);
        $order->update(['status' => 'delivered']);
        $order->fulfillment?->update(['status' => 'delivered', 'delivered_at' => now()]);
        return $order->fresh(['fulfillment']);
    }

    public function cancel(string $orderId, string $cancelledBy): Order
    {
        $order = Order::whereIn('status', ['pending', 'confirmed'])->findOrFail($orderId);
        $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        return $order->fresh();
    }

    public function requestReturn(string $orderId, string $requestedBy, string $reason): OrderReturn
    {
        $order = Order::where('status', 'delivered')->findOrFail($orderId);

        return OrderReturn::create([
            'order_id'     => $orderId,
            'requested_by' => $requestedBy,
            'reason'       => $reason,
            'status'       => 'pending',
        ]);
    }

    public function approveReturn(string $returnId, string $reviewedBy, string $resolution, ?float $refundAmount): OrderReturn
    {
        $return = OrderReturn::where('status', 'pending')->findOrFail($returnId);
        $return->update([
            'status'        => 'approved',
            'resolution'    => $resolution,
            'refund_amount' => $refundAmount,
            'reviewed_by'   => $reviewedBy,
            'reviewed_at'   => now(),
        ]);

        $return->order->update(['status' => 'refunded']);

        return $return->fresh();
    }

    private function generateOrderNumber(): string
    {
        $prefix = config('commerce.order_prefix', 'ORD');
        $year   = now()->year;
        $last   = Order::where('order_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('created_at', 'desc')
            ->value('order_number');
        $seq = $last ? ((int) substr($last, -6)) + 1 : 1;
        return sprintf('%s-%d-%06d', $prefix, $year, $seq);
    }
}