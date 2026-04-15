<?php

namespace Modules\Logistics\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Logistics\Services\CourierService;

class CourierController extends Controller
{
    public function __construct(protected CourierService $couriers) {}

    /** GET /api/v1/logistics/orgs/{orgId}/couriers */
    public function indexAccounts(string $orgId): JsonResponse
    {
        return response()->json($this->couriers->listAccounts($orgId));
    }

    /** POST /api/v1/logistics/orgs/{orgId}/couriers */
    public function storeAccount(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'courier' => ['required', 'string'],
            'name'    => ['required', 'string'],
        ]);
        $account = $this->couriers->createAccount($orgId, $request->all());
        return response()->json(['message' => 'Courier account added.', 'account' => $account], 201);
    }

    /** GET /api/v1/logistics/orgs/{orgId}/shipments */
    public function indexShipments(Request $request, string $orgId): JsonResponse
    {
        return response()->json($this->couriers->listShipments($orgId, $request->only(['status', 'courier']), (int) $request->get('per_page', 25)));
    }

    /** POST /api/v1/logistics/orgs/{orgId}/shipments */
    public function bookShipment(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'courier_account_id' => ['required', 'string', 'size:26'],
            'recipient_name'     => ['required', 'string'],
            'delivery_address'   => ['required', 'string'],
        ]);
        $shipment = $this->couriers->bookShipment($orgId, $request->courier_account_id, $request->all());
        return response()->json(['message' => 'Shipment booked.', 'shipment' => $shipment], 201);
    }

    /** POST /api/v1/logistics/shipments/{id}/sync */
    public function syncStatus(string $id): JsonResponse
    {
        $shipment = $this->couriers->syncShipmentStatus($id);
        return response()->json(['message' => 'Status synced.', 'shipment' => $shipment]);
    }
}
