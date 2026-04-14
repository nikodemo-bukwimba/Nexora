<?php

namespace Modules\Logistics\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Logistics\Models\DeliveryProof;
use Modules\Logistics\Services\DeliveryRunService;

class DeliveryRunController extends Controller
{
    public function __construct(protected DeliveryRunService $runs) {}

    /** GET /api/v1/logistics/orgs/{orgId}/runs */
    public function index(Request $request, string $orgId): JsonResponse
    {
        return response()->json($this->runs->list($orgId, $request->only(['driver_id', 'status', 'date']), (int) $request->get('per_page', 25)));
    }

    /** POST /api/v1/logistics/orgs/{orgId}/runs */
    public function store(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'scheduled_date' => ['required', 'date'],
        ]);
        $run = $this->runs->create($orgId, $request->user()->actor_id, $request->all());
        return response()->json(['message' => 'Delivery run created.', 'run' => $run], 201);
    }

    /** GET /api/v1/logistics/runs/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json($this->runs->get($id));
    }

    /** POST /api/v1/logistics/runs/{id}/dispatch */
    public function dispatch(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'driver_id'  => ['required', 'string', 'size:26'],
            'vehicle_id' => ['required', 'string', 'size:26'],
        ]);
        $run = $this->runs->dispatch($id, $request->driver_id, $request->vehicle_id);
        return response()->json(['message' => 'Run dispatched.', 'run' => $run]);
    }

    /** POST /api/v1/logistics/runs/{id}/start */
    public function start(Request $request, string $id): JsonResponse
    {
        $run = $this->runs->startRun($id, $request->user()->actor_id);
        return response()->json(['message' => 'Run started.', 'run' => $run]);
    }

    /** POST /api/v1/logistics/runs/{id}/stops */
    public function addStop(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'recipient_name' => ['required', 'string'],
            'address'        => ['required', 'string'],
        ]);

        $orgId = $request->header('X-Org-Id') ?? $request->org_id;
        $stop  = $this->runs->addStop($id, $orgId, $request->all());
        return response()->json(['message' => 'Stop added.', 'stop' => $stop], 201);
    }

    /** PATCH /api/v1/logistics/runs/{id}/stops/reorder */
    public function reorderStops(Request $request, string $id): JsonResponse
    {
        $request->validate(['stops' => ['required', 'array']]);
        $this->runs->reorderStops($id, $request->stops);
        return response()->json(['message' => 'Stops reordered.']);
    }

    /** PATCH /api/v1/logistics/stops/{stopId}/status */
    public function updateStopStatus(Request $request, string $stopId): JsonResponse
    {
        $request->validate([
            'status'         => ['required', 'string'],
            'failure_reason' => ['nullable', 'string', 'in:not_home,wrong_address,refused,damaged,other'],
        ]);
        $stop = $this->runs->updateStopStatus($stopId, $request->user()->actor_id, $request->status, $request->all());
        return response()->json(['message' => 'Stop status updated.', 'stop' => $stop]);
    }

    /** POST /api/v1/logistics/stops/{stopId}/proof */
    public function recordProof(Request $request, string $stopId): JsonResponse
    {
        $data = [];

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = $file->store("delivery-proofs/{$stopId}", config('logistics.media_disk', 'public'));
            $data['photo_url'] = Storage::disk(config('logistics.media_disk', 'public'))->url($path);
        }

        // Handle signature (base64 image from mobile)
        if ($request->filled('signature_base64')) {
            $sigData  = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->signature_base64));
            $sigPath  = "delivery-proofs/{$stopId}/signature.png";
            Storage::disk(config('logistics.media_disk', 'public'))->put($sigPath, $sigData);
            $data['signature_url']    = Storage::disk(config('logistics.media_disk', 'public'))->url($sigPath);
            $data['signed_by_name']   = $request->signed_by_name ?? null;
        }

        // Confirmation code
        if ($request->filled('confirmation_code')) {
            $data['confirmation_code']  = $request->confirmation_code;
            $data['code_confirmed_at']  = now();
        }

        // GPS
        $data['photo_latitude']  = $request->latitude ?? null;
        $data['photo_longitude'] = $request->longitude ?? null;

        $proof = $this->runs->recordProof($stopId, $request->user()->actor_id, $data);
        return response()->json(['message' => 'Proof of delivery recorded.', 'proof' => $proof]);
    }
}
