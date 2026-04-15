<?php

namespace Modules\Logistics\Services;

use Modules\Logistics\Models\CourierShipment;
use Modules\Logistics\Models\DeliveryStop;

/**
 * Unified order tracking service.
 * Given an order_id, finds whether it's being delivered by own fleet or courier
 * and returns a normalized tracking response.
 */
class OrderTrackingService
{
    public function getTrackingForOrder(string $orderId): array
    {
        // Check own fleet first
        $stop = DeliveryStop::where('order_id', $orderId)
            ->with(['run.driver', 'run.vehicle', 'statusLogs', 'proof'])
            ->latest()
            ->first();

        if ($stop) {
            return $this->formatOwnFleetTracking($stop);
        }

        // Check third-party courier
        $shipment = CourierShipment::where('order_id', $orderId)
            ->with(['courierAccount'])
            ->latest()
            ->first();

        if ($shipment) {
            return $this->formatCourierTracking($shipment);
        }

        return [
            'order_id'       => $orderId,
            'delivery_method' => null,
            'status'          => 'not_dispatched',
            'message'         => 'Order has not been dispatched for delivery yet.',
        ];
    }

    private function formatOwnFleetTracking(DeliveryStop $stop): array
    {
        $statusLabels = [
            'pending'    => 'Order queued for delivery',
            'en_route'   => 'Driver is on the way',
            'arrived'    => 'Driver has arrived at destination',
            'delivered'  => 'Order delivered successfully',
            'failed'     => 'Delivery attempt failed',
            'rescheduled' => 'Delivery rescheduled',
        ];

        return [
            'order_id'         => $stop->order_id,
            'order_number'     => $stop->order_number,
            'delivery_method'  => 'own_fleet',
            'status'           => $stop->status,
            'status_label'     => $statusLabels[$stop->status] ?? $stop->status,
            'run_number'       => $stop->run->run_number ?? null,
            'driver_name'      => $stop->run?->driver?->name,
            'driver_phone'     => $stop->run?->driver?->phone,
            'scheduled_date'   => $stop->run?->scheduled_date,
            'estimated_arrival' => $stop->estimated_arrival_at,
            'delivered_at'     => $stop->delivered_at,
            'failed_at'        => $stop->failed_at,
            'failure_reason'   => $stop->failure_reason,
            'failure_notes'    => $stop->failure_notes,
            'rescheduled_date' => $stop->rescheduled_date,
            'has_proof'        => $stop->proof !== null,
            'history'          => $stop->statusLogs->map(fn($log) => [
                'from'       => $log->from_status,
                'to'         => $log->to_status,
                'at'         => $log->created_at,
                'notes'      => $log->notes,
            ]),
        ];
    }

    private function formatCourierTracking(CourierShipment $shipment): array
    {
        return [
            'order_id'         => $shipment->order_id,
            'order_number'     => $shipment->order_number,
            'delivery_method'  => 'courier',
            'courier'          => $shipment->courierAccount->courier,
            'courier_name'     => $shipment->courierAccount->name,
            'tracking_number'  => $shipment->tracking_number,
            'tracking_url'     => $shipment->tracking_url,
            'status'           => $shipment->status,
            'booked_at'        => $shipment->booked_at,
            'picked_up_at'     => $shipment->picked_up_at,
            'delivered_at'     => $shipment->delivered_at,
            'failed_at'        => $shipment->failed_at,
            'estimated_delivery_at' => $shipment->estimated_delivery_at,
            'failure_reason'   => $shipment->failure_reason,
            'courier_events'   => $shipment->courier_events ?? [],
        ];
    }
}
