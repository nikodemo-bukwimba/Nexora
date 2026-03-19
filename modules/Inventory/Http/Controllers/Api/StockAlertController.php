<?php

namespace Modules\Inventory\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Contracts\Services\StockAlertServiceInterface;

class StockAlertController extends Controller
{
    public function __construct(protected StockAlertServiceInterface $alerts) {}

    /** GET /api/v1/inventory/orgs/{orgId}/alerts */
    public function index(Request $request, string $orgId): JsonResponse
    {
        return response()->json(
            $this->alerts->listAlerts($orgId, $request->only(['type', 'status', 'warehouse_id']), (int) $request->get('per_page', 25))
        );
    }

    /** POST /api/v1/inventory/alerts/{id}/acknowledge */
    public function acknowledge(Request $request, string $id): JsonResponse
    {
        $alert = $this->alerts->acknowledge($id, $request->user()->actor_id ?? 'system');
        return response()->json(['message' => 'Alert acknowledged.', 'alert' => $alert]);
    }

    /** POST /api/v1/inventory/alerts/{id}/resolve */
    public function resolve(string $id): JsonResponse
    {
        $alert = $this->alerts->resolve($id);
        return response()->json(['message' => 'Alert resolved.', 'alert' => $alert]);
    }
}
