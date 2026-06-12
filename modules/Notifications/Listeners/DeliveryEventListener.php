<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\Services\NotificationService;

/**
 * Listens to Logistics module delivery events and sends push notifications.
 * Register in your EventServiceProvider or via a dedicated event in the Logistics module.
 *
 * Alternatively, DeliveryNotificationService can be called directly from
 * DeliveryRunService::updateStopStatus() — both patterns work. The listener
 * pattern is preferred when you want loose coupling.
 */
class DeliveryEventListener
{
    public function __construct(
        protected NotificationService $notifications
    ) {}

    /**
     * Handle delivery stop status change events.
     * Expected payload shape:
     * {
     *   order_id, order_number, stop_id, stop_status,
     *   buyer_actor_id, failure_reason?, rescheduled_date?
     * }
     */
    public function handleStopStatusChanged(array $payload): void
    {
        $actorId = $payload['buyer_actor_id'] ?? null;
        if (! $actorId) return;

        $status      = $payload['stop_status']  ?? '';
        $orderNumber = $payload['order_number'] ?? 'your order';

        [$type, $title, $body] = match ($status) {
            'en_route'    => ['delivery.en_route',    'Driver on the way', "Your order #{$orderNumber} is on its way to you."],
            'arrived'     => ['delivery.arrived',     'Driver has arrived', "Your driver has arrived for order #{$orderNumber}."],
            'delivered'   => ['delivery.delivered',   'Order Delivered!', "Order #{$orderNumber} was delivered successfully."],
            'failed'      => ['delivery.failed',      'Delivery Unsuccessful', "Delivery for #{$orderNumber} was unsuccessful."],
            'rescheduled' => ['delivery.rescheduled', 'Delivery Rescheduled', "Delivery for #{$orderNumber} has been rescheduled."],
            default       => [null, null, null],
        };

        if (! $type) return;

        try {
            $this->notifications->send(
                actorId:   $actorId,
                type:      $type,
                title:     $title,
                body:      $body,
                actionUrl: "/orders/{$payload['order_id']}",
                refType:   'Order',
                refId:     $payload['order_id'],
                data:      $payload,
            );
        } catch (\Throwable $e) {
            Log::warning("DeliveryEventListener: notification failed: " . $e->getMessage());
        }
    }
}