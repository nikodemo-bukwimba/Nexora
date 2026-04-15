<?php

namespace Modules\Logistics\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Logistics\Models\DeliveryRate;
use Modules\Logistics\Models\DeliveryZone;
use Modules\Logistics\Services\CostCalculationService;

class ZoneRateController extends Controller
{
    public function __construct(protected CostCalculationService $costs) {}

    /** GET /api/v1/logistics/orgs/{orgId}/zones */
    public function indexZones(Request $request, string $orgId): JsonResponse
    {
        $zones = DeliveryZone::where('org_id', $orgId)->where('is_active', true)->orderBy('name')->get();
        return response()->json($zones);
    }

    /** POST /api/v1/logistics/orgs/{orgId}/zones */
    public function storeZone(Request $request, string $orgId): JsonResponse
    {
        $request->validate(['name' => ['required', 'string']]);
        $zone = DeliveryZone::create(array_merge($request->all(), ['org_id' => $orgId]));
        return response()->json(['message' => 'Zone created.', 'zone' => $zone], 201);
    }

    /** GET /api/v1/logistics/orgs/{orgId}/rates */
    public function indexRates(Request $request, string $orgId): JsonResponse
    {
        $rates = DeliveryRate::where('org_id', $orgId)->where('is_active', true)->with(['zone'])->get();
        return response()->json($rates);
    }

    /** POST /api/v1/logistics/orgs/{orgId}/rates */
    public function storeRate(Request $request, string $orgId): JsonResponse
    {
        $request->validate(['name' => ['required', 'string']]);
        $rate = DeliveryRate::create(array_merge($request->all(), ['org_id' => $orgId]));
        return response()->json(['message' => 'Rate created.', 'rate' => $rate], 201);
    }

    /** POST /api/v1/logistics/orgs/{orgId}/rates/preview */
    public function previewCost(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'zone_id'    => ['required', 'string', 'size:26'],
            'unit_count' => ['required', 'integer', 'min:1'],
        ]);
        $preview = $this->costs->previewCost($orgId, $request->zone_id, $request->unit_count, (float) ($request->weight_kg ?? 0));
        return response()->json($preview);
    }
}
