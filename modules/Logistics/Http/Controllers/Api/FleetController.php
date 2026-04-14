<?php

namespace Modules\Logistics\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Logistics\Services\FleetService;

class FleetController extends Controller
{
    public function __construct(protected FleetService $fleet) {}

    // ── Vehicles ───────────────────────────────────────────────

    /** GET /api/v1/logistics/orgs/{orgId}/vehicles */
    public function indexVehicles(Request $request, string $orgId): JsonResponse
    {
        return response()->json($this->fleet->listVehicles($orgId, $request->only(['status', 'type']), (int) $request->get('per_page', 25)));
    }

    /** POST /api/v1/logistics/orgs/{orgId}/vehicles */
    public function storeVehicle(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'registration' => ['required', 'string', 'max:50'],
            'type'         => ['required', 'string', 'in:truck,van,motorcycle,bicycle'],
        ]);
        $vehicle = $this->fleet->createVehicle($orgId, $request->all());
        return response()->json(['message' => 'Vehicle added.', 'vehicle' => $vehicle], 201);
    }

    /** PATCH /api/v1/logistics/vehicles/{id} */
    public function updateVehicle(Request $request, string $id): JsonResponse
    {
        $vehicle = $this->fleet->updateVehicle($id, $request->all());
        return response()->json(['message' => 'Vehicle updated.', 'vehicle' => $vehicle]);
    }

    // ── Drivers ────────────────────────────────────────────────

    /** GET /api/v1/logistics/orgs/{orgId}/drivers */
    public function indexDrivers(Request $request, string $orgId): JsonResponse
    {
        return response()->json($this->fleet->listDrivers($orgId, $request->only(['status', 'availability']), (int) $request->get('per_page', 25)));
    }

    /** POST /api/v1/logistics/orgs/{orgId}/drivers */
    public function storeDriver(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'actor_id' => ['required', 'string', 'size:26'],
            'name'     => ['required', 'string'],
        ]);
        $driver = $this->fleet->createDriver($orgId, $request->all());
        return response()->json(['message' => 'Driver registered.', 'driver' => $driver], 201);
    }

    /** POST /api/v1/logistics/drivers/availability */
    public function updateAvailability(Request $request): JsonResponse
    {
        $request->validate(['availability' => ['required', 'string', 'in:online,offline']]);
        $driver = $this->fleet->updateDriverAvailability($request->user()->actor_id, $request->availability);
        return response()->json(['message' => 'Availability updated.', 'driver' => $driver]);
    }
}
