<?php

namespace Modules\PharmaMarketing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PharmaMarketing\Models\ProductUpdateDelivery;

/**
 * Exposes ProductUpdateDelivery records as "notifications" so the Flutter
 * apps can list, inspect, and retry failed delivery attempts.
 *
 * Base URL: /api/v1/pharma/orgs/{orgId}/notifications
 */
class NotificationController extends Controller
{
    /** GET /api/v1/pharma/orgs/{orgId}/notifications */
    public function index(Request $request, string $orgId): JsonResponse
    {
        $deliveries = ProductUpdateDelivery::whereHas(
                'productUpdate',
                fn($q) => $q->where('org_id', $orgId)
            )
            ->with(['productUpdate', 'customer'])
            ->when($request->status,  fn($q, $v) => $q->where('status', $v))
            ->when($request->channel, fn($q, $v) => $q->where('channel', $v))
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 50));

        return response()->json([
            'data' => $deliveries->map(fn($d) => $this->toNotification($d)),
            'meta' => [
                'total'        => $deliveries->total(),
                'current_page' => $deliveries->currentPage(),
                'last_page'    => $deliveries->lastPage(),
            ],
        ]);
    }

    /** GET /api/v1/pharma/orgs/{orgId}/notifications/{id} */
    public function show(string $orgId, string $id): JsonResponse
    {
        $delivery = ProductUpdateDelivery::whereHas(
                'productUpdate',
                fn($q) => $q->where('org_id', $orgId)
            )
            ->with(['productUpdate', 'customer'])
            ->findOrFail($id);

        return response()->json(['data' => $this->toNotification($delivery)]);
    }

    /** POST /api/v1/pharma/orgs/{orgId}/notifications/{id}/retry */
    public function retry(string $orgId, string $id): JsonResponse
    {
        $delivery = ProductUpdateDelivery::whereHas(
                'productUpdate',
                fn($q) => $q->where('org_id', $orgId)
            )
            ->where('status', 'failed')
            ->with(['productUpdate', 'customer'])
            ->findOrFail($id);

        $delivery->update([
            'status'         => 'pending',
            'failure_reason' => null,
        ]);

        \Modules\PharmaMarketing\Jobs\SendProductUpdateToCustomer::dispatch(
            $delivery,
            $delivery->productUpdate,
            $delivery->customer
        );

        return response()->json([
            'message' => 'Notification queued for retry.',
            'data'    => $this->toNotification($delivery->fresh()),
        ]);
    }

    // ── Shape delivery record as notification ──────────────────

    private function toNotification(ProductUpdateDelivery $d): array
    {
        $update = $d->productUpdate;

        return [
            'id'             => $d->id,
            'recipient_id'   => $d->customer_id,
            'recipient_type' => 'customer',
            'channel'        => $d->channel,
            'content'        => $update?->title
                                    ? "Re: {$update->title}"
                                    : '(Product Update)',
            'template_id'    => $update?->update_type,
            'status'         => $this->mapStatus($d->status),
            'sent_at'        => $d->sent_at?->toISOString(),
            'delivered_at'   => $d->delivered_at?->toISOString(),
            'failure_reason' => $d->failure_reason,
            'created_at'     => $d->created_at?->toISOString() ?? now()->toISOString(),
        ];
    }

    // pending/sending → queued  (matches Flutter NotificationStatus)
    private function mapStatus(string $status): string
    {
        return match ($status) {
            'pending', 'sending' => 'queued',
            default              => $status, // sent | delivered | failed pass through
        };
    }
}