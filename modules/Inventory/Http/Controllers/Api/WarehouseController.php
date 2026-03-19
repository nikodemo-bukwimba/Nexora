<?php

namespace Modules\Inventory\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Contracts\Services\WarehouseServiceInterface;

class WarehouseController extends Controller
{
    public function __construct(protected WarehouseServiceInterface $warehouses) {}

    /** GET /api/v1/inventory/orgs/{orgId}/warehouses */
    public function index(Request $request, string $orgId): JsonResponse
    {
        return response()->json($this->warehouses->listForOrg($orgId, (int) $request->get('per_page', 25)));
    }

    /** POST /api/v1/inventory/orgs/{orgId}/warehouses */
    public function store(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:standard,cold,bonded,virtual'],
        ]);
        $warehouse = $this->warehouses->create($orgId, $request->all());
        return response()->json(['message' => 'Warehouse created.', 'warehouse' => $warehouse], 201);
    }

    /** GET /api/v1/inventory/warehouses/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json($this->warehouses->get($id));
    }

    /** PATCH /api/v1/inventory/warehouses/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $warehouse = $this->warehouses->update($id, $request->all());
        return response()->json(['message' => 'Warehouse updated.', 'warehouse' => $warehouse]);
    }

    /** POST /api/v1/inventory/warehouses/{id}/deactivate */
    public function deactivate(string $id): JsonResponse
    {
        $warehouse = $this->warehouses->deactivate($id);
        return response()->json(['message' => 'Warehouse deactivated.', 'warehouse' => $warehouse]);
    }
}
