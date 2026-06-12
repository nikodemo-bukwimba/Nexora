<?php

namespace Modules\Logistics\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Logistics\Models\Driver;
use Modules\Logistics\Services\DeliveryNotificationService;
use Modules\Logistics\Services\DeliveryRunService;
use Modules\Logistics\Services\DriverLocationService;
use Modules\Logistics\Services\FleetService;

/**
 * Driver-facing API — all endpoints scoped to the authenticated driver.
 * The driver authenticates with their platform user credentials (Sanctum token).
 * Their actor_id links to the Driver record via lg_drivers.actor_id.
 */
class DriverAppController extends Controller
{
    public function __construct(
        protected DeliveryRunService          $runs,
        protected DriverLocationService       $locations,
        protected FleetService                $fleet,
        protected DeliveryNotificationService $deliveryNotifications,
    ) {}

    // ── Driver profile ─────────────────────────────────────────

    /**
     * GET /api/v1/logistics/driver/me
     * Returns the driver profile for the authenticated actor.
     */
    public function me(Request $request): JsonResponse
    {
        $driver = Driver::where('actor_id', $request->user()->actor_id)
            ->with(['runs' => fn($q) => $q->whereIn('status', ['dispatched', 'in_progress'])])
            ->firstOrFail();

        $activeRun = $driver->activeRun();

        return response()->json([
            'driver'     => $driver,
            'active_run' => $activeRun?->load(['stops', 'vehicle']),
        ]);
    }

    // ── My runs ────────────────────────────────────────────────

    /**
     * GET /api/v1/logistics/driver/runs
     * List runs assigned to this driver (today's by default).
     * Query params: status, date
     */
    public function myRuns(Request $request): JsonResponse
    {
        $driver = Driver::where('actor_id', $request->user()->actor_id)->firstOrFail();

        $runs = \Modules\Logistics\Models\DeliveryRun::where('driver_id', $driver->id)
            ->when(
                $request->get('status'),
                fn($q, $v) => $q->where('status', $v),
                fn($q) => $q->whereIn('status', ['dispatched', 'in_progress', 'completed', 'partially_completed'])
            )
            ->when(
                $request->get('date'),
                fn($q, $v) => $q->where('scheduled_date', $v),
                fn($q) => $q->where('scheduled_date', '>=', now()->subDays(7)->toDateString())
            )
            ->with(['stops', 'vehicle'])
            ->orderBy('scheduled_date', 'desc')
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'dispatched' THEN 1 ELSE 2 END")
            ->paginate((int) $request->get('per_page', 10));

        return response()->json($runs);
    }

    /**
     * GET /api/v1/logistics/driver/runs/{id}
     * Full run detail with all stops, proofs, and status logs.
     */
    public function showRun(Request $request, string $id): JsonResponse
    {
        $driver = Driver::where('actor_id', $request->user()->actor_id)->firstOrFail();

        $run = \Modules\Logistics\Models\DeliveryRun::where('id', $id)
            ->where('driver_id', $driver->id)
            ->with(['stops.proof', 'stops.statusLogs', 'vehicle'])
            ->firstOrFail();

        return response()->json($run);
    }

    /**
     * POST /api/v1/logistics/driver/runs/{id}/start
     * Driver starts their assigned run.
     */
    public function startRun(Request $request, string $id): JsonResponse
    {
        $run = $this->runs->startRun($id, $request->user()->actor_id);

        // Notify customers on dispatched stops
        foreach ($run->stops as $stop) {
            $this->deliveryNotifications->notifyRunDispatched($stop);
        }

        return response()->json(['message' => 'Run started.', 'run' => $run]);
    }

    // ── Stop lifecycle ─────────────────────────────────────────

    /**
     * PATCH /api/v1/logistics/driver/stops/{stopId}/status
     * Transition a stop through its lifecycle.
     * Triggers customer push notification on each transition.
     */
    public function updateStopStatus(Request $request, string $stopId): JsonResponse
    {
        $request->validate([
            'status'           => ['required', 'string', 'in:en_route,arrived,delivered,failed,rescheduled'],
            'latitude'         => ['nullable', 'numeric'],
            'longitude'        => ['nullable', 'numeric'],
            'failure_reason'   => ['nullable', 'string', 'in:not_home,wrong_address,refused,damaged,other'],
            'failure_notes'    => ['nullable', 'string'],
            'rescheduled_date' => ['nullable', 'date'],
            'notes'            => ['nullable', 'string'],
        ]);

        $stop = $this->runs->updateStopStatus(
            $stopId,
            $request->user()->actor_id,
            $request->status,
            $request->all()
        );

        // Fire customer notification for meaningful transitions
        $this->deliveryNotifications->notifyStopStatusChange($stop, $request->status);

        return response()->json(['message' => 'Stop status updated.', 'stop' => $stop]);
    }

    /**
     * POST /api/v1/logistics/driver/stops/{stopId}/proof
     * Record proof of delivery (photo / signature / code).
     */
    public function recordProof(Request $request, string $stopId): JsonResponse
    {
        $data = [];

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = $file->store("delivery-proofs/{$stopId}", config('logistics.media_disk', 'public'));
            $data['photo_url'] = Storage::disk(config('logistics.media_disk', 'public'))->url($path);
        }

        if ($request->filled('signature_base64')) {
            $sigData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->signature_base64));
            $sigPath = "delivery-proofs/{$stopId}/signature.png";
            Storage::disk(config('logistics.media_disk', 'public'))->put($sigPath, $sigData);
            $data['signature_url']  = Storage::disk(config('logistics.media_disk', 'public'))->url($sigPath);
            $data['signed_by_name'] = $request->signed_by_name ?? null;
        }

        if ($request->filled('confirmation_code')) {
            $data['confirmation_code'] = $request->confirmation_code;
            $data['code_confirmed_at'] = now();
        }

        $data['photo_latitude']  = $request->latitude  ?? null;
        $data['photo_longitude'] = $request->longitude ?? null;

        $proof = $this->runs->recordProof($stopId, $request->user()->actor_id, $data);

        return response()->json(['message' => 'Proof recorded.', 'proof' => $proof]);
    }

    // ── Location ────────────────────────────────────────────────

    /**
     * POST /api/v1/logistics/driver/location
     * Driver pings their GPS position. Called periodically by the delivery app.
     * Recommended frequency: every 15–30 seconds while on a run.
     */
    public function pingLocation(Request $request): JsonResponse
    {
        $request->validate([
            'latitude'         => ['required', 'numeric', 'between:-90,90'],
            'longitude'        => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters'  => ['nullable', 'numeric', 'min:0'],
            'speed_kmh'        => ['nullable', 'numeric', 'min:0'],
            'heading_degrees'  => ['nullable', 'numeric', 'between:0,360'],
            'source'           => ['nullable', 'string', 'in:gps,network,passive'],
            'recorded_at'      => ['nullable', 'date'],
        ]);

        $position = $this->locations->ping($request->user()->actor_id, $request->all());

        return response()->json([
            'message'  => 'Location updated.',
            'position' => $position,
        ]);
    }

    /**
     * GET /api/v1/logistics/driver/runs/{id}/location-history
     * Driver's location breadcrumb trail for a run.
     * Primarily for admin dashboard; driver app may also use this for self-review.
     */
    public function locationHistory(Request $request, string $id): JsonResponse
    {
        $driver = Driver::where('actor_id', $request->user()->actor_id)->firstOrFail();

        $history = $this->locations->getLocationHistory($driver->id, $id, (int) $request->get('limit', 100));

        return response()->json(['history' => $history]);
    }

    // ── Availability ────────────────────────────────────────────

    /**
     * POST /api/v1/logistics/driver/availability
     * Toggle driver online/offline.
     */
    public function updateAvailability(Request $request): JsonResponse
    {
        $request->validate(['availability' => ['required', 'string', 'in:online,offline']]);
        $driver = $this->fleet->updateDriverAvailability($request->user()->actor_id, $request->availability);
        return response()->json(['message' => 'Availability updated.', 'driver' => $driver]);
    }
}