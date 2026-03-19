<?php

namespace Modules\Inventory\Contracts\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\StockReservation;

interface InventoryServiceInterface
{
    // ── Batches ────────────────────────────────────────────────
    public function receiveBatch(string $warehouseId, string $productId, string $orgId, array $data): InventoryBatch;
    public function getBatch(string $id): InventoryBatch;
    public function listBatches(string $orgId, array $filters, int $perPage): LengthAwarePaginator;
    public function getStockForProduct(string $productId, string $orgId): Collection;
    public function getTotalStock(string $productId, string $orgId): int;

    // ── Movements ──────────────────────────────────────────────
    public function adjustStock(string $batchId, int $quantityDelta, string $type, string $performedBy, ?string $refType, ?string $refId, ?string $notes): StockMovement;
    public function getMovements(string $batchId, int $perPage): LengthAwarePaginator;
    public function transferStock(string $fromBatchId, string $toWarehouseId, int $quantity, string $performedBy): StockMovement;

    // ── Reservations ───────────────────────────────────────────
    public function reserve(string $batchId, int $quantity, string $refType, string $refId, ?string $expiresAt): StockReservation;
    public function releaseReservation(string $reservationId): void;
    public function fulfillReservation(string $reservationId): void;
}
