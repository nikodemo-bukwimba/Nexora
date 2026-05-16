<?php

namespace Modules\Inventory\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Contracts\Services\InventoryServiceInterface;

class InventoryController extends Controller
{
    public function __construct(protected InventoryServiceInterface $inventory) {}

    /**
     * POST /api/v1/inventory/warehouses/{warehouseId}/receive
     *
     * Receives a physical stock delivery into a warehouse batch.
     * variant_id is now required — it is the link between a Commerce
     * product variant and its inventory batches.
     */
    public function receive(Request $request, string $warehouseId): JsonResponse
    {
        $request->validate([
            'product_id'   => ['required', 'string', 'size:26'],
            'variant_id'   => ['required', 'string', 'size:26'],  // ← now required
            'org_id'       => ['required', 'string', 'size:26'],
            'quantity'     => ['required', 'integer', 'min:1'],
            'unit_cost'    => ['nullable', 'numeric', 'min:0'],
            'currency'     => ['nullable', 'string', 'size:3'],
            'sku'          => ['nullable', 'string', 'max:100'],
            'batch_number' => ['nullable', 'string', 'max:100'],
            'expires_at'   => ['nullable', 'date'],
            'best_before_at' => ['nullable', 'date'],
            'notes'        => ['nullable', 'string'],
        ]);

        $batch = $this->inventory->receiveBatch(
            warehouseId: $warehouseId,
            productId:   $request->product_id,
            variantId:   $request->variant_id,   // ← passed through
            orgId:       $request->org_id,
            data:        array_merge(
                $request->all(),
                ['performed_by' => $request->user()->actor_id ?? null]
            )
        );

        return response()->json(['message' => 'Stock received.', 'batch' => $batch], 201);
    }

    /** GET /api/v1/inventory/batches/{id} */
    public function showBatch(string $id): JsonResponse
    {
        return response()->json($this->inventory->getBatch($id));
    }

    /**
     * GET /api/v1/inventory/orgs/{orgId}/batches
     *
     * Supports filtering by variant_id in addition to existing filters.
     */
    public function listBatches(Request $request, string $orgId): JsonResponse
    {
        return response()->json(
            $this->inventory->listBatches(
                $orgId,
                $request->only(['warehouse_id', 'product_id', 'variant_id', 'status', 'sku']),  // ← variant_id added
                (int) $request->get('per_page', 25)
            )
        );
    }

    /**
     * GET /api/v1/inventory/orgs/{orgId}/products/{productId}/stock
     *
     * Returns stock overview for a product, broken down by variant
     * when variant_id query param is supplied.
     *
     * Without ?variant_id → returns all batches for the product (product-level view)
     * With    ?variant_id → returns batches for that specific variant only
     */
    public function stockForProduct(Request $request, string $orgId, string $productId): JsonResponse
    {
        $variantId = $request->query('variant_id');

        $batches = $this->inventory->getStockForProduct($productId, $orgId, $variantId);
        $total   = $this->inventory->getTotalStock($productId, $orgId, $variantId);

        return response()->json([
            'product_id'  => $productId,
            'variant_id'  => $variantId,   // null when not filtered
            'total_stock' => $total,
            'batches'     => $batches,
        ]);
    }

    /**
     * GET /api/v1/inventory/orgs/{orgId}/variants/{variantId}/stock
     *
     * Dedicated variant-level stock endpoint.
     * Returns FEFO-ordered active batches + total available for one variant.
     */
    public function stockForVariant(Request $request, string $orgId, string $variantId): JsonResponse
    {
        // Resolve product_id from variant — cross-module read via commerce connection
        $variant = \Modules\Commerce\Models\ProductVariant::findOrFail($variantId);

        $batches = $this->inventory->getStockForProduct($variant->product_id, $orgId, $variantId);
        $total   = $this->inventory->getTotalStock($variant->product_id, $orgId, $variantId);

        return response()->json([
            'variant_id'   => $variantId,
            'product_id'   => $variant->product_id,
            'variant_name' => $variant->name,
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

        $reservation = $this->inventory->reserve(
            $id,
            $request->quantity,
            $request->ref_type,
            $request->ref_id,
            $request->expires_at ?? null
        );

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