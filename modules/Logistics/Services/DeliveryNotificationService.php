<?php

namespace Modules\Logistics\Services;

use Illuminate\Support\Facades\Log;
use Modules\Logistics\Models\DeliveryStop;
use Modules\Notifications\Services\NotificationService;

/**
 * Fires push notifications to customers when delivery stop status changes.
 * Resolves the customer's actor_id from the linked Commerce order.
 */
class DeliveryNotificationService
{
    public function __construct(
        protected NotificationService $notifications
    ) {}

    /**
     * Called after a stop status is updated.
     * Resolves customer actor_id from the commerce order and sends the correct push.
     */
    public function notifyStopStatusChange(DeliveryStop $stop, string $newStatus): void
    {
        if (! $stop->order_id) return;

        $actorId = $this->resolveCustomerActorId($stop->order_id);
        if (! $actorId) return;

        [$type, $title, $body] = match ($newStatus) {
            'en_route' => [
                'delivery.en_route',
                'Driver on the way',
                "Your order #{$stop->order_number} is on its way to you.",
            ],
            'arrived' => [
                'delivery.arrived',
                'Driver has arrived',
                "Your driver has arrived at the delivery address for order #{$stop->order_number}.",
            ],
            'delivered' => [
                'delivery.delivered',
                'Order Delivered!',
                "Your order #{$stop->order_number} has been delivered successfully.",
            ],
            'failed' => [
                'delivery.failed',
                'Delivery Unsuccessful',
                "We were unable to deliver order #{$stop->order_number}. " . $this->failureReasonLabel($stop->failure_reason),
            ],
            'rescheduled' => [
                'delivery.rescheduled',
                'Delivery Rescheduled',
                "Your delivery for order #{$stop->order_number} has been rescheduled to {$stop->rescheduled_date?->format('M d, Y')}.",
            ],
            default => null,
        };

        if (! $type) return;

        try {
            $this->notifications->send(
                actorId:   $actorId,
                type:      $type,
                title:     $title,
                body:      $body,
                actionUrl: "/orders/{$stop->order_id}",
                refType:   'Order',
                refId:     $stop->order_id,
                data: [
                    'order_id'     => $stop->order_id,
                    'order_number' => $stop->order_number,
                    'stop_id'      => $stop->id,
                    'stop_status'  => $newStatus,
                ],
            );
        } catch (\Throwable $e) {
            Log::warning("DeliveryNotificationService: failed to notify actor {$actorId} for stop {$stop->id}: " . $e->getMessage());
        }
    }

    /**
     * Notify customer when a run is dispatched (order picked up from warehouse).
     */
    public function notifyRunDispatched(DeliveryStop $stop): void
    {
        if (! $stop->order_id) return;

        $actorId = $this->resolveCustomerActorId($stop->order_id);
        if (! $actorId) return;

        try {
            $this->notifications->send(
                actorId:   $actorId,
                type:      'delivery.dispatched',
                title:     'Order Dispatched for Delivery',
                body:      "Your order #{$stop->order_number} has been dispatched and will be delivered today.",
                actionUrl: "/orders/{$stop->order_id}",
                refType:   'Order',
                refId:     $stop->order_id,
                data: [
                    'order_id'       => $stop->order_id,
                    'order_number'   => $stop->order_number,
                    'scheduled_date' => $stop->run?->scheduled_date?->toDateString(),
                ],
            );
        } catch (\Throwable $e) {
            Log::warning("DeliveryNotificationService: dispatch notify failed for stop {$stop->id}: " . $e->getMessage());
        }
    }

    // ── Private helpers ────────────────────────────────────────

    private function resolveCustomerActorId(string $orderId): ?string
    {
        try {
            $order = \DB::connection('commerce')
                ->table('orders')
                ->where('id', $orderId)
                ->select(['buyer_actor_id', 'metadata'])
                ->first();

            if (! $order) return null;

            // buyer_actor_id is the direct actor_id on the order
            if ($order->buyer_actor_id) {
                return $order->buyer_actor_id;
            }

            // Fallback: customer in pharma_marketing linked via platform_user_id
            $metadata = is_string($order->metadata) ? json_decode($order->metadata, true) : (array) $order->metadata;
            $customerId = $metadata['customer_id'] ?? null;

            if ($customerId) {
                $customer = \DB::connection('pharma_marketing')
                    ->table('pm_customers')
                    ->where('id', $customerId)
                    ->select(['platform_user_id'])
                    ->first();

                if ($customer?->platform_user_id) {
                    $user = \DB::connection('platform')
                        ->table('users')
                        ->where('id', $customer->platform_user_id)
                        ->value('actor_id');
                    return $user;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("DeliveryNotificationService: could not resolve customer actor_id for order {$orderId}: " . $e->getMessage());
        }

        return null;
    }

    private function failureReasonLabel(?string $reason): string
    {
        return match ($reason) {
            'not_home'      => 'No one was available to receive the order.',
            'wrong_address' => 'The delivery address could not be located.',
            'refused'       => 'The delivery was refused.',
            'damaged'       => 'The item was found damaged.',
            default         => 'Please contact support for more information.',
        };
    }
}