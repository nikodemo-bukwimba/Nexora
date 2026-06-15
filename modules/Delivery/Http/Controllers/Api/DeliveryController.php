<?php

// === FILE: Modules/Delivery/Http/Controllers/Api/DeliveryController.php

namespace Modules\Delivery\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Delivery\Models\Delivery;
use Modules\Notifications\Services\NotificationService;

class DeliveryController extends Controller
{
    public function __construct(
        protected NotificationService $notifications,
    ) {}

    // ── Valid status transitions ────────────────────────────────
    private const TRANSITIONS = [
        'pending'    => ['in_transit', 'cancelled'],
        'in_transit' => ['delivered',  'failed', 'cancelled'],
        'failed'     => ['pending'],
    ];

    // ── GET /api/v1/orgs/{orgId}/deliveries ────────────────────

    public function index(Request $request, string $orgId): JsonResponse
    {
        $deliveries = Delivery::forOrg($orgId)
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->search, fn($q, $v) =>
                $q->where(fn($q2) =>
                    $q2->where('receiver_name',    'ilike', "%{$v}%")
                       ->orWhere('sender_name',    'ilike', "%{$v}%")
                       ->orWhere('car_registration','ilike', "%{$v}%")
                       ->orWhere('tracking_number', 'ilike', "%{$v}%")
                ))
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 50));

        return response()->json($deliveries);
    }

    // ── POST /api/v1/orgs/{orgId}/deliveries ───────────────────

    public function store(Request $request, string $orgId): JsonResponse
    {
        $data = $request->validate([
            'order_id'          => ['nullable', 'string'],
            'transporter_name'  => ['required', 'string', 'max:255'],
            'transporter_phone' => ['required', 'string', 'max:20'],
            'car_registration'  => ['required', 'string', 'max:20'],
            'cargo_fare'        => ['required', 'numeric', 'min:0'],
            'fare_is_paid'      => ['boolean'],
            'sender_name'       => ['required', 'string', 'max:255'],
            'sender_location'   => ['required', 'string', 'max:255'],
            'sender_phone'      => ['nullable', 'string', 'max:20'],
            'receiver_name'     => ['required', 'string', 'max:255'],
            'receiver_location' => ['required', 'string', 'max:255'],
            'receiver_phone'    => ['nullable', 'string', 'max:20'],
            'notes'             => ['nullable', 'string', 'max:1000'],
            'estimated_arrival' => ['nullable', 'date'],
        ]);

        $delivery = Delivery::create(array_merge($data, ['org_id' => $orgId]));

        return response()->json([
            'message'  => 'Delivery created.',
            'delivery' => $delivery,
        ], 201);
    }

    // ── GET /api/v1/orgs/{orgId}/deliveries/{id} ───────────────

    public function show(string $orgId, string $id): JsonResponse
    {
        $delivery = Delivery::forOrg($orgId)->findOrFail($id);
        return response()->json(['delivery' => $delivery]);
    }

    // ── PATCH /api/v1/orgs/{orgId}/deliveries/{id} ─────────────

    public function update(Request $request, string $orgId, string $id): JsonResponse
    {
        $delivery = Delivery::forOrg($orgId)->findOrFail($id);

        $data = $request->validate([
            'transporter_name'  => ['sometimes', 'string', 'max:255'],
            'transporter_phone' => ['sometimes', 'string', 'max:20'],
            'car_registration'  => ['sometimes', 'string', 'max:20'],
            'cargo_fare'        => ['sometimes', 'numeric', 'min:0'],
            'fare_is_paid'      => ['sometimes', 'boolean'],
            'sender_name'       => ['sometimes', 'string', 'max:255'],
            'sender_location'   => ['sometimes', 'string', 'max:255'],
            'sender_phone'      => ['nullable',  'string', 'max:20'],
            'receiver_name'     => ['sometimes', 'string', 'max:255'],
            'receiver_location' => ['sometimes', 'string', 'max:255'],
            'receiver_phone'    => ['nullable',  'string', 'max:20'],
            'notes'             => ['nullable',  'string', 'max:1000'],
            'estimated_arrival' => ['nullable',  'date'],
            // Invoice fields are editable by admin before confirm
            'invoice_number'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'invoice_date'      => ['sometimes', 'nullable', 'date'],
            'invoice_value'     => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'invoice_comment'   => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $delivery->update($data);

        return response()->json([
            'message'  => 'Delivery updated.',
            'delivery' => $delivery->fresh(),
        ]);
    }

    // ── POST /api/v1/orgs/{orgId}/deliveries/{id}/confirm ──────
    //
    //  Customer-side confirmation: invoice details + signed document.
    //  Transitions delivery to 'delivered' and syncs Commerce.

    public function confirm(Request $request, string $orgId, string $id): JsonResponse
    {
        $delivery = Delivery::forOrg($orgId)->findOrFail($id);

        if ($delivery->status !== 'in_transit') {
            return response()->json([
                'message' => 'Delivery can only be confirmed when in_transit.',
            ], 422);
        }

        $data = $request->validate([
            'invoice_number'  => ['required', 'string', 'max:100'],
            'invoice_date'    => ['required', 'date'],
            'invoice_value'   => ['required', 'numeric', 'min:0.01'],
            'invoice_comment' => ['nullable', 'string', 'max:2000'],
            'signed_invoice'  => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
        ]);

        $disk = config('delivery.media_disk', 'public');
        $path = $request->file('signed_invoice')
            ->store("deliveries/{$id}/signed_invoice", $disk);

        $delivery->update([
            'invoice_number'      => $data['invoice_number'],
            'invoice_date'        => $data['invoice_date'],
            'invoice_value'       => $data['invoice_value'],
            'invoice_comment'     => $data['invoice_comment'] ?? null,
            'signed_invoice_path' => Storage::disk($disk)->url($path),
            'status'              => 'delivered',
            'delivered_at'        => now(),
        ]);

        if ($delivery->order_id) {
            try {
                DB::connection('commerce')
                    ->table('orders')
                    ->where('id', $delivery->order_id)
                    ->update(['status' => 'delivered', 'updated_at' => now()]);

                DB::connection('commerce')
                    ->table('order_fulfillments')
                    ->where('order_id', $delivery->order_id)
                    ->update([
                        'status'       => 'delivered',
                        'delivered_at' => now(),
                        'updated_at'   => now(),
                    ]);
            } catch (\Throwable $e) {
                Log::warning(
                    "Delivery confirm: failed to sync commerce order {$delivery->order_id}: "
                    . $e->getMessage()
                );
            }

            $this->notifyBuyerForTransition($delivery->fresh(), 'delivered');
        }

        return response()->json([
            'message'  => 'Delivery confirmed.',
            'delivery' => $delivery->fresh(),
        ]);
    }

    // ── PATCH /api/v1/orgs/{orgId}/deliveries/{id}/transition ──

    public function transition(Request $request, string $orgId, string $id): JsonResponse
    {
        $delivery = Delivery::forOrg($orgId)->findOrFail($id);

        $request->validate([
            'status' => ['required', 'string',
                'in:pending,in_transit,delivered,cancelled,failed'],
        ]);

        $newStatus = $request->status;
        $allowed   = self::TRANSITIONS[$delivery->status] ?? [];

        if (! in_array($newStatus, $allowed)) {
            return response()->json([
                'message' => "Cannot transition from {$delivery->status} to {$newStatus}.",
            ], 422);
        }

        $extra = [];

        if ($newStatus === 'delivered') {
            $extra['delivered_at'] = now();
        }

        if ($newStatus === 'cancelled') {
            $extra['cancelled_at'] = now();
        }

        $delivery->update(array_merge(['status' => $newStatus], $extra));

        /*
        |--------------------------------------------------------------------------
        | Sync Commerce Order Status
        |--------------------------------------------------------------------------
        */
        if ($delivery->order_id) {

            $commerceStatusMap = [
                'in_transit' => 'shipped',
                'delivered'  => 'delivered',
            ];

            if (isset($commerceStatusMap[$newStatus])) {
                try {
                    DB::connection('commerce')
                        ->table('orders')
                        ->where('id', $delivery->order_id)
                        ->update([
                            'status'     => $commerceStatusMap[$newStatus],
                            'updated_at' => now(),
                        ]);

                    if ($newStatus === 'delivered') {
                        DB::connection('commerce')
                            ->table('order_fulfillments')
                            ->where('order_id', $delivery->order_id)
                            ->update([
                                'status'       => 'delivered',
                                'delivered_at' => now(),
                                'updated_at'   => now(),
                            ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning(
                        "Delivery: failed to sync commerce order {$delivery->order_id}: "
                        . $e->getMessage()
                    );
                }
            }

            $this->notifyBuyerForTransition($delivery, $newStatus);
        }

        return response()->json([
            'message'  => "Delivery status updated to {$newStatus}.",
            'delivery' => $delivery->fresh(),
        ]);
    }

    // ── PATCH /api/v1/orgs/{orgId}/deliveries/{id}/location ────

    public function updateLocation(Request $request, string $orgId, string $id): JsonResponse
    {
        $delivery = Delivery::forOrg($orgId)->findOrFail($id);

        if ($delivery->isTerminal()) {
            return response()->json(['message' => 'Delivery is terminal.'], 422);
        }

        $data = $request->validate([
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $delivery->update([
            'driver_latitude'     => $data['latitude'],
            'driver_longitude'    => $data['longitude'],
            'location_updated_at' => now(),
        ]);

        return response()->json(['message' => 'Location updated.'], 200);
    }

    // ── POST /api/v1/orgs/{orgId}/deliveries/{id}/images ───────

    public function uploadImages(Request $request, string $orgId, string $id): JsonResponse
    {
        $delivery = Delivery::forOrg($orgId)->findOrFail($id);

        $request->validate([
            'parcel_image'  => ['nullable', 'image', 'max:5120'],
            'waybill_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $updates = [];
        $disk    = config('delivery.media_disk', 'public');

        if ($request->hasFile('parcel_image')) {
            $path = $request->file('parcel_image')
                ->store("deliveries/{$id}/parcel", $disk);
            $updates['parcel_image_path'] = Storage::disk($disk)->url($path);
        }

        if ($request->hasFile('waybill_image')) {
            $path = $request->file('waybill_image')
                ->store("deliveries/{$id}/waybill", $disk);
            $updates['waybill_image_path'] = Storage::disk($disk)->url($path);
        }

        if (! empty($updates)) {
            $delivery->update($updates);
        }

        $delivery = $delivery->fresh();

        return response()->json([
            'message'            => 'Images uploaded.',
            'parcel_image_path'  => $delivery->parcel_image_path,
            'waybill_image_path' => $delivery->waybill_image_path,
        ]);
    }

    // ── DELETE /api/v1/orgs/{orgId}/deliveries/{id} ────────────

    public function destroy(string $orgId, string $id): JsonResponse
    {
        $delivery = Delivery::forOrg($orgId)->findOrFail($id);
        $delivery->delete();
        return response()->json(['message' => 'Delivery deleted.']);
    }

    // ── GET /api/v1/track/{trackingNumber}  (PUBLIC — no auth) ──

    public function publicTrack(string $trackingNumber): JsonResponse
    {
        $delivery = Delivery::where('tracking_number', strtoupper($trackingNumber))->first();

        if (! $delivery) {
            return response()->json([
                'message' => "No delivery found with tracking number {$trackingNumber}.",
            ], 404);
        }

        return response()->json($delivery->toPublicTracking());
    }

    // ── Private helpers ─────────────────────────────────────────

    private function notifyBuyerForTransition(Delivery $delivery, string $newStatus): void
    {
        try {
            $order = DB::connection('commerce')
                ->table('orders')
                ->where('id', $delivery->order_id)
                ->select('buyer_actor_id', 'order_number')
                ->first();

            if (! $order?->buyer_actor_id) {
                return;
            }

            [$type, $title, $body] = match ($newStatus) {
                'in_transit' => [
                    'delivery.in_transit',
                    'Your order is on the way',
                    "Your delivery ({$delivery->tracking_number}) is now in transit. " .
                    "Transporter: {$delivery->transporter_name}, {$delivery->car_registration}.",
                ],
                'delivered' => [
                    'delivery.delivered',
                    'Order delivered',
                    "Your delivery ({$delivery->tracking_number}) has been delivered. Thank you!",
                ],
                'cancelled' => [
                    'delivery.cancelled',
                    'Delivery cancelled',
                    "Your delivery ({$delivery->tracking_number}) has been cancelled.",
                ],
                'failed' => [
                    'delivery.failed',
                    'Delivery failed',
                    "Your delivery ({$delivery->tracking_number}) could not be completed. Please contact support.",
                ],
                default => [null, null, null],
            };

            if (! $type) {
                return;
            }

            $this->notifications->send(
                actorId: $order->buyer_actor_id,
                type:    $type,
                title:   $title,
                body:    $body,
                refType: 'delivery',
                refId:   $delivery->id,
                data:    [
                    'tracking_number' => $delivery->tracking_number,
                    'order_number'    => $order->order_number ?? null,
                ],
            );
        } catch (\Throwable $e) {
            Log::warning("Delivery: failed to notify buyer for {$delivery->id}: " . $e->getMessage());
        }
    }
}