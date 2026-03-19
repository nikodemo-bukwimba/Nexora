<?php

namespace Modules\Inventory\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Contracts\Services\InventoryServiceInterface;

class InventoryController extends Controller
{
    public function __construct(protected InventoryServiceInterface $inventory) {}

    /** POST /api/v1/inventory/warehouses/{warehouseId}/receive */
    public function receive(Request $request, string $warehouseId): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'string', 'size:26'],
            'org_id'     => ['required', 'string', 'size:26'],
            'quantity'   => ['required', 'integer', 'min:1'],
            'unit_cost'  => ['nullable', 'numeric', 'min:0'],
            'sku'        => ['nullable', 'string'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $batch = $this->inventory->receiveBatch(
            $warehouseId,
            $request->product_id,
            $request->org_id,
            array_merge($request->all(), ['performed_by' => $request->user()->actor_id ?? null])
        );

        return response()->json(['message' => 'Stock received.', 'batch' => $batch], 201);
    }

    /** GET /api/v1/inventory/batches/{id} */
    public function showBatch(string $id): JsonResponse
    {
        return response()->json($this->inventory->getBatch($id));
    }

    /** GET /api/v1/inventory/orgs/{orgId}/batches */
    public function listBatches(Request $request, string $orgId): JsonResponse
    {
        return response()->json(
            $this->inventory->listBatches($orgId, $request->only(['warehouse_id', 'product_id', 'status', 'sku']), (int) $request->get('per_page', 25))
        );
    }

    /** GET /api/v1/inventory/orgs/{orgId}/products/{productId}/stock */
    public function stockForProduct(string $orgId, string $productId): JsonResponse
    {
        $batches = $this->inventory->getStockForProduct($productId, $orgId);
        $total   = $this->inventory->getTotalStock($productId, $orgId);

        return response()->json([
            'product_id'   => $productId,
            'total_stock'  => $total,
            'batches'      => $batches,
        ]);
    }

    /** POST /api/v1/inventory/batches/{id}/adjust */
    public function adjust(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'quantity_delta' => ['required', 'integer'],
            'type'           => ['required', 'string', 'in:adjusted,damaged,returned'],
            'notes'          => ['nullable', 'string'],
        ]);

        $movement = $this->inventory->adjustStock(
            $id,
            $request->quantity_delta,
            $request->type,
            $request->user()->actor_id ?? 'system',
            $request->ref_type ?? null,
            $request->ref_id ?? null,
            $request->notes ?? null
        );

        return response()->json(['message' => 'Stock adjusted.', 'movement' => $movement]);
    }

    /** GET /api/v1/inventory/batches/{id}/movements */
    public function movements(Request $request, string $id): JsonResponse
    {
        return response()->json($this->inventory->getMovements($id, (int) $request->get('per_page', 50)));
    }

    /** POST /api/v1/inventory/batches/{id}/transfer */
    public function transfer(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'to_warehouse_id' => ['required', 'string', 'size:26'],
            'quantity'        => ['required', 'integer', 'min:1'],
        ]);

        $movement = $this->inventory->transferStock(
            $id,
            $request->to_warehouse_id,
            $request->quantity,
            $request->user()->actor_id ?? 'system'
        );

        return response()->json(['message' => 'Stock transferred.', 'movement' => $movement]);
    }

    /** POST /api/v1/inventory/batches/{id}/reserve */
    public function reserve(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'quantity'   => ['required', 'integer', 'min:1'],
            'ref_type'   => ['required', 'string'],
            'ref_id'     => ['required', 'string'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $reservation = $this->inventory->reserve($id, $request->quantity, $request->ref_type, $request->ref_id, $request->expires_at ?? null);
        return response()->json(['message' => 'Stock reserved.', 'reservation' => $reservation], 201);
    }

    /** POST /api/v1/inventory/reservations/{id}/release */
    public function releaseReservation(string $id): JsonResponse
    {
        $this->inventory->releaseReservation($id);
        return response()->json(['message' => 'Reservation released.']);
    }

    /** POST /api/v1/inventory/reservations/{id}/fulfill */
    public function fulfillReservation(string $id): JsonResponse
    {
        $this->inventory->fulfillReservation($id);
        return response()->json(['message' => 'Reservation fulfilled.']);
    }
}
