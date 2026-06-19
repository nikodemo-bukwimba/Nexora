<?php

namespace Modules\Commerce\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Contracts\Services\InventoryServiceInterface;
use Modules\Inventory\Models\StockReservation;

class InventoryDeductionService
{
    public function __construct(
        protected InventoryServiceInterface $inventory,
    ) {}

    /**
     * Reserve stock for every trackable item in the order.
     *
     * IMPORTANT: this no longer deducts quantity_available directly.
     * It only places a StockReservation (increments quantity_reserved),
     * which is reversible. Call fulfillReservations() once the Commerce
     * transaction has actually committed, or releaseReservations() if
     * it didn't.
     *
     * Returns the list of reservation IDs created, so the caller can
     * fulfill or release them after the Commerce transaction resolves.
     *
     * @return string[] reservation IDs
     */
    public function reserveForOrder(
        string $orgId,
        string $orderId,
        array  $items,
    ): array {
        $reservationIds = [];

        foreach ($items as $item) {
            if (empty($item['track_inventory'])) {
                continue;
            }

            $reservationIds = array_merge(
                $reservationIds,
                $this->reserveItem(
                    orgId:       $orgId,
                    orderId:     $orderId,
                    productId:   $item['product_id'],
                    variantId:   $item['variant_id'] ?? null,
                    variantName: $item['variant_name'],
                    quantity:    (int) $item['quantity'],
                )
            );
        }

        return $reservationIds;
    }

    /**
     * Fulfill a batch of reservations — the actual quantity_available
     * decrement + 'sold' movement log happens here. Call this ONLY
     * after the order row has actually committed to the database.
     */
    public function fulfillReservations(array $reservationIds): void
    {
        foreach ($reservationIds as $id) {
            try {
                $this->inventory->fulfillReservation($id);
            } catch (\Throwable $e) {
                // A reservation failing to fulfill post-commit is a data
                // integrity concern, not a request-failure concern — the
                // order already exists. Log loudly so ops can reconcile,
                // but never throw here: the customer's order succeeded.
                Log::error("InventoryDeductionService: failed to fulfill reservation {$id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Release a batch of reservations — used when the order failed to
     * commit (or any later step failed) so the held stock is returned
     * to availability immediately rather than waiting for expiry.
     */
    public function releaseReservations(array $reservationIds): void
    {
        foreach ($reservationIds as $id) {
            try {
                $this->inventory->releaseReservation($id);
            } catch (\Throwable $e) {
                Log::warning("InventoryDeductionService: failed to release reservation {$id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Release all active reservations tied to a ref (order). Safety-net
     * for callers that don't have the reservation IDs in hand (e.g. a
     * cleanup job sweeping orders that never reached 'confirmed').
     */
    public function releaseAllForRef(string $refType, string $refId): void
    {
        $ids = StockReservation::forRef($refType, $refId)->pluck('id')->all();
        $this->releaseReservations($ids);
    }

    private function reserveItem(
        string  $orgId,
        string  $orderId,
        string  $productId,
        ?string $variantId,
        string  $variantName,
        int     $quantity,
    ): array {
        // ── Step 1: resolve batches ────────────────────────────
        // Priority: branch own stock → branch product-level → root stock → root product-level.
        // This allows centrally-received stock (root org) to be consumed by branches.
        $batches = $variantId !== null
            ? $this->inventory->getStockForProduct($productId, $orgId, $variantId)
            : collect();

        if ($batches->isEmpty()) {
            $batches = $this->inventory->getStockForProduct($productId, $orgId, null);
        }

        if ($batches->isEmpty()) {
            $rootOrgId = DB::connection('platform')
                ->table('organizations')
                ->where('id', $orgId)
                ->value('root_org_id');

            if ($rootOrgId && $rootOrgId !== $orgId) {
                $batches = $variantId !== null
                    ? $this->inventory->getStockForProduct($productId, $rootOrgId, $variantId)
                    : collect();

                if ($batches->isEmpty()) {
                    $batches = $this->inventory->getStockForProduct($productId, $rootOrgId, null);
                }
            }
        }

        // ── Step 2: pre-flight aggregate check ────────────────
        $totalAvailable = $batches->sum(
            fn($b) => $b->quantity_available - $b->quantity_reserved
        );

        if ($totalAvailable < $quantity) {
            throw new \RuntimeException(
                "Insufficient stock for \"{$variantName}\". "
                . "Available: {$totalAvailable}, requested: {$quantity}."
            );
        }

        // ── Step 3: FEFO reserve, splitting across batches if needed ──
        $remaining       = $quantity;
        $reservationIds  = [];
        $createdSoFar    = [];

        try {
            foreach ($batches as $batch) {
                if ($remaining <= 0) break;

                $batchAvailable = $batch->quantity_available - $batch->quantity_reserved;
                if ($batchAvailable <= 0) continue;

                $toReserve = min($remaining, $batchAvailable);
                $remaining -= $toReserve;

                $reservation = $this->inventory->reserve(
                    batchId:   $batch->id,
                    quantity:  $toReserve,
                    refType:   'Order',
                    refId:     $orderId,
                    expiresAt: now()->addMinutes(30)->toISOString(),
                );

                $reservationIds[] = $reservation->id;
                $createdSoFar[]   = $reservation->id;
            }
        } catch (\Throwable $e) {
            // Partial reservation across batches failed midway (e.g. a
            // race condition between the pre-flight check and the actual
            // reserve calls). Roll back what we already reserved for
            // THIS item before propagating, so we don't leave orphaned
            // holds on other batches.
            $this->releaseReservations($createdSoFar);
            throw $e;
        }

        return $reservationIds;
    }
}