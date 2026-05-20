<?php

namespace Modules\Inventory\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Contracts\Services\InventoryServiceInterface;
use Modules\Inventory\Contracts\Services\StockAlertServiceInterface;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\StockReservation;
use Modules\Inventory\Models\Warehouse;

class InventoryService implements InventoryServiceInterface
{
    public function __construct(
        protected StockAlertServiceInterface $alertService
    ) {}

    // ── Batches ────────────────────────────────────────────────

    public function receiveBatch(
        string  $warehouseId,
        string  $productId,
        ?string $variantId,       // ← new parameter
        string  $orgId,
        array   $data
    ): InventoryBatch {
        return DB::connection('inventory')->transaction(function () use (
            $warehouseId, $productId, $variantId, $orgId, $data
        ) {
            $batch = InventoryBatch::create([
                'warehouse_id'       => $warehouseId,
                'product_id'         => $productId,
                'variant_id'         => $variantId,           // ← stored
                'org_id'             => $orgId,
                'batch_number'       => $data['batch_number'] ?? null,
                'sku'                => $data['sku'] ?? null,
                'quantity_received'  => $data['quantity'],
                'quantity_available' => $data['quantity'],
                'quantity_reserved'  => 0,
                'quantity_damaged'   => 0,
                'unit_cost'          => $data['unit_cost'] ?? null,
                'currency'           => $data['currency'] ?? 'TZS',
                'status'             => 'active',
                'expires_at'         => $data['expires_at'] ?? null,
                'best_before_at'     => $data['best_before_at'] ?? null,
                'metadata'           => $data['metadata'] ?? null,
            ]);

            // Record inbound movement — include variant_id on the ledger row
            StockMovement::create([
                'batch_id'        => $batch->id,
                'warehouse_id'    => $warehouseId,
                'product_id'      => $productId,
                'variant_id'      => $variantId,              // ← stored
                'org_id'          => $orgId,
                'type'            => 'received',
                'quantity'        => $data['quantity'],
                'quantity_before' => 0,
                'quantity_after'  => $data['quantity'],
                'ref_type'        => $data['ref_type'] ?? null,
                'ref_id'          => $data['ref_id'] ?? null,
                'performed_by'    => $data['performed_by'] ?? null,
                'notes'           => $data['notes'] ?? 'Stock received',
            ]);

            $this->alertService->checkAndCreateAlerts($batch->id);

            return $batch->fresh(['warehouse']);
        });
    }

    public function getBatch(string $id): InventoryBatch
    {
        return InventoryBatch::with(['warehouse'])->findOrFail($id);
    }

    public function listBatches(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        // Resolve the full org scope: root + all its branches.
        // If $orgId is already a branch, this returns just [$orgId].
        $orgIds = $this->resolveOrgScope($orgId);

        return InventoryBatch::whereIn('org_id', $orgIds)          // ← was where('org_id', $orgId)
            ->when(isset($filters['warehouse_id']), fn($q) => $q->where('warehouse_id', $filters['warehouse_id']))
            ->when(isset($filters['product_id']),   fn($q) => $q->where('product_id',   $filters['product_id']))
            ->when(isset($filters['variant_id']),   fn($q) => $q->where('variant_id',   $filters['variant_id']))
            ->when(isset($filters['status']),       fn($q) => $q->where('status',       $filters['status']))
            ->when(isset($filters['sku']),          fn($q) => $q->where('sku', 'ilike', "%{$filters['sku']}%"))
            ->with(['warehouse'])
            ->orderBy('received_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Returns [$orgId] when it's a branch, or [$orgId, ...branchIds] when root.
     * Crosses into the Platform module via a lightweight query.
     */
    private function resolveOrgScope(string $orgId): array
    {
        // Platform orgs live on a different connection — raw query to avoid
        // pulling the full Platform module as a hard dependency.
        $branches = \DB::connection('platform')
            ->table('organizations')
            ->where('root_org_id', $orgId)   // rows whose root IS this org
            ->where('id', '!=', $orgId)      // exclude root itself to avoid dup
            ->pluck('id')
            ->all();

        return array_merge([$orgId], $branches);
    }

    /**
     * Return FEFO-ordered active batches.
     *
     * When $variantId is provided → scopes to that exact variant (preferred).
     * When $variantId is null     → falls back to product-level scoping so
     *                               existing callers without variant context
     *                               continue to work.
     */
    public function getStockForProduct(
        string  $productId,
        string  $orgId,
        ?string $variantId = null
    ): Collection {
        $orgIds = $this->resolveOrgScope($orgId);   // ← add this

        return InventoryBatch::where('product_id', $productId)
            ->whereIn('org_id', $orgIds)            // ← was where('org_id', $orgId)
            ->where('status', 'active')
            ->where('quantity_available', '>', 0)
            ->when($variantId !== null, fn($q) => $q->where('variant_id', $variantId))
            ->with(['warehouse'])
            ->orderBy('expires_at')
            ->get();
    }

    public function getTotalStock(string $productId, string $orgId, ?string $variantId = null): int
    {
        $orgIds = $this->resolveOrgScope($orgId);

        return InventoryBatch::where('product_id', $productId)
            ->whereIn('org_id', $orgIds)
            ->where('status', 'active')
            ->when($variantId !== null, fn($q) => $q->where('variant_id', $variantId))
            ->sum('quantity_available');
    }

    // ── Movements ──────────────────────────────────────────────

    public function adjustStock(
        string  $batchId,
        int     $quantityDelta,
        string  $type,
        string  $performedBy,
        ?string $refType,
        ?string $refId,
        ?string $notes
    ): StockMovement {
        return DB::connection('inventory')->transaction(function () use (
            $batchId, $quantityDelta, $type, $performedBy, $refType, $refId, $notes
        ) {
            $batch  = InventoryBatch::lockForUpdate()->findOrFail($batchId);
            $before = $batch->quantity_available;
            $after  = $before + $quantityDelta;

            if ($after < 0) {
                throw new \RuntimeException(
                    "Insufficient stock. Available: {$before}, requested deduction: " . abs($quantityDelta)
                );
            }

            $batch->update([
                'quantity_available' => $after,
                'status'             => $after === 0 ? 'depleted' : $batch->status,
            ]);

            // Carry variant_id from the batch onto the movement ledger row
            $movement = StockMovement::create([
                'batch_id'        => $batchId,
                'warehouse_id'    => $batch->warehouse_id,
                'product_id'      => $batch->product_id,
                'variant_id'      => $batch->variant_id,       // ← propagated
                'org_id'          => $batch->org_id,
                'type'            => $type,
                'quantity'        => $quantityDelta,
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'ref_type'        => $refType,
                'ref_id'          => $refId,
                'performed_by'    => $performedBy,
                'notes'           => $notes,
            ]);

            $this->alertService->checkAndCreateAlerts($batchId);

            return $movement;
        });
    }

    public function getMovements(string $batchId, int $perPage): LengthAwarePaginator
    {
        return StockMovement::where('batch_id', $batchId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function transferStock(
        string $fromBatchId,
        string $toWarehouseId,
        int    $quantity,
        string $performedBy
    ): StockMovement {
        return DB::connection('inventory')->transaction(function () use (
            $fromBatchId, $toWarehouseId, $quantity, $performedBy
        ) {
            $fromBatch   = InventoryBatch::lockForUpdate()->findOrFail($fromBatchId);
            $toWarehouse = Warehouse::findOrFail($toWarehouseId);

            if ($fromBatch->quantity_available < $quantity) {
                throw new \RuntimeException('Insufficient stock for transfer.');
            }

            // New batch in destination — carry variant_id across
            $newBatch = InventoryBatch::create([
                'warehouse_id'       => $toWarehouseId,
                'product_id'         => $fromBatch->product_id,
                'variant_id'         => $fromBatch->variant_id,  // ← carried over
                'org_id'             => $fromBatch->org_id,
                'batch_number'       => $fromBatch->batch_number,
                'sku'                => $fromBatch->sku,
                'quantity_received'  => $quantity,
                'quantity_available' => $quantity,
                'unit_cost'          => $fromBatch->unit_cost,
                'currency'           => $fromBatch->currency,
                'status'             => 'active',
                'expires_at'         => $fromBatch->expires_at,
                'best_before_at'     => $fromBatch->best_before_at,
                'metadata'           => $fromBatch->metadata,
            ]);

            // Deduct from source (adjustStock propagates variant_id automatically)
            $outMovement = $this->adjustStock(
                $fromBatchId, -$quantity, 'transferred', $performedBy,
                'InventoryBatch', $newBatch->id,
                "Transferred to warehouse: {$toWarehouse->name}"
            );

            // Inbound at destination
            StockMovement::create([
                'batch_id'        => $newBatch->id,
                'warehouse_id'    => $toWarehouseId,
                'product_id'      => $newBatch->product_id,
                'variant_id'      => $newBatch->variant_id,     // ← carried over
                'org_id'          => $newBatch->org_id,
                'type'            => 'received',
                'quantity'        => $quantity,
                'quantity_before' => 0,
                'quantity_after'  => $quantity,
                'ref_type'        => 'InventoryBatch',
                'ref_id'          => $fromBatchId,
                'performed_by'    => $performedBy,
                'notes'           => "Transferred from warehouse: {$fromBatch->warehouse->name}",
            ]);

            return $outMovement;
        });
    }

    // ── Reservations ───────────────────────────────────────────

    public function reserve(
        string  $batchId,
        int     $quantity,
        string  $refType,
        string  $refId,
        ?string $expiresAt
    ): StockReservation {
        return DB::connection('inventory')->transaction(function () use (
            $batchId, $quantity, $refType, $refId, $expiresAt
        ) {
            $batch     = InventoryBatch::lockForUpdate()->findOrFail($batchId);
            $available = $batch->quantity_available - $batch->quantity_reserved;

            if ($available < $quantity) {
                throw new \RuntimeException(
                    "Insufficient available stock. Available: {$available}, requested: {$quantity}"
                );
            }

            $batch->increment('quantity_reserved', $quantity);

            return StockReservation::create([
                'batch_id'   => $batchId,
                'product_id' => $batch->product_id,
                'org_id'     => $batch->org_id,
                'quantity'   => $quantity,
                'ref_type'   => $refType,
                'ref_id'     => $refId,
                'status'     => 'active',
                'expires_at' => $expiresAt,
            ]);
        });
    }

    public function releaseReservation(string $reservationId): void
    {
        DB::connection('inventory')->transaction(function () use ($reservationId) {
            $reservation = StockReservation::where('status', 'active')->lockForUpdate()->findOrFail($reservationId);
            $batch       = InventoryBatch::lockForUpdate()->findOrFail($reservation->batch_id);

            $batch->decrement('quantity_reserved', $reservation->quantity);
            $reservation->update(['status' => 'released']);
        });
    }

    public function fulfillReservation(string $reservationId): void
    {
        DB::connection('inventory')->transaction(function () use ($reservationId) {
            $reservation = StockReservation::where('status', 'active')->lockForUpdate()->findOrFail($reservationId);
            $batch       = InventoryBatch::lockForUpdate()->findOrFail($reservation->batch_id);

            $before = $batch->quantity_available;
            $after  = $before - $reservation->quantity;

            $batch->update([
                'quantity_available' => $after,
                'quantity_reserved'  => $batch->quantity_reserved - $reservation->quantity,
                'status'             => $after === 0 ? 'depleted' : $batch->status,
            ]);

            StockMovement::create([
                'batch_id'        => $reservation->batch_id,
                'warehouse_id'    => $batch->warehouse_id,
                'product_id'      => $batch->product_id,
                'variant_id'      => $batch->variant_id,        // ← propagated
                'org_id'          => $batch->org_id,
                'type'            => 'sold',
                'quantity'        => -$reservation->quantity,
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'ref_type'        => $reservation->ref_type,
                'ref_id'          => $reservation->ref_id,
                'notes'           => 'Reservation fulfilled',
            ]);

            $reservation->update(['status' => 'fulfilled']);
            $this->alertService->checkAndCreateAlerts($reservation->batch_id);
        });
    }
}