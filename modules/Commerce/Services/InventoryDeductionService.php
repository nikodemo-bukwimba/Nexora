<?php

namespace Modules\Commerce\Services;

use Modules\Inventory\Contracts\Services\InventoryServiceInterface;

/**
 * Shared FEFO stock deduction used by both checkout paths:
 *   - OrderService::checkout()          (basket / customer app)
 *   - OrderController::adminStore()     (admin dashboard)
 *
 * Only deducts when the product has track_inventory = true.
 * Throws \RuntimeException with a user-friendly message when
 * any item has insufficient stock — caller should catch this
 * and return a 422 response.
 *
 * Must be called INSIDE an existing DB transaction so that a
 * stock failure rolls back the order write automatically.
 */
class InventoryDeductionService
{
    public function __construct(
        protected InventoryServiceInterface $inventory,
    ) {}

    /**
     * Deduct stock for a list of order items using FEFO batch order.
     *
     * Each item must have:
     *   'product_id'      => string
     *   'variant_id'      => string        ← variant-level deduction
     *   'variant_name'    => string        (used in error messages)
     *   'quantity'        => int
     *   'track_inventory' => bool          (from product — skip when false)
     *
     * @throws \RuntimeException  When stock is insufficient for any tracked item
     */
    public function deductForOrder(
        string $orgId,
        string $orderId,
        string $performedBy,
        array  $items,
    ): void {
        foreach ($items as $item) {
            if (empty($item['track_inventory'])) {
                continue;
            }

            $this->deductItem(
                orgId:       $orgId,
                orderId:     $orderId,
                performedBy: $performedBy,
                productId:   $item['product_id'],
                variantId:   $item['variant_id'] ?? null,
                variantName: $item['variant_name'],
                quantity:    (int) $item['quantity'],
            );
        }
    }

    // ── Private ───────────────────────────────────────────────

    /**
     * Drain FEFO-ordered batches for a single variant.
     *
     * Resolution order:
     *   1. Try variant-scoped batches (variant_id match)           ← preferred
     *   2. Fall back to product-level batches when variant_id is
     *      null OR no variant-scoped batches exist yet (e.g. stock
     *      was received before variant_id column was added)
     *
     * Spans multiple batches automatically when needed.
     */
    private function deductItem(
        string  $orgId,
        string  $orderId,
        string  $performedBy,
        string  $productId,
        ?string $variantId,
        string  $variantName,
        int     $quantity,
    ): void {
        // ── Step 1: resolve batches ────────────────────────────
        $batches = null;

        if ($variantId !== null) {
            $batches = $this->inventory->getStockForProduct(
                $productId, $orgId, $variantId
            );
        }

        // Fall back to product-level when no variant-scoped batches found
        if ($batches === null || $batches->isEmpty()) {
            $batches = $this->inventory->getStockForProduct(
                $productId, $orgId, null
            );
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

        // ── Step 3: FEFO drain ────────────────────────────────
        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $batchAvailable = $batch->quantity_available - $batch->quantity_reserved;

            if ($batchAvailable <= 0) {
                continue;
            }

            $toDeduct  = min($remaining, $batchAvailable);
            $remaining -= $toDeduct;

            $this->inventory->adjustStock(
                batchId:       $batch->id,
                quantityDelta: -$toDeduct,
                type:          'sold',
                performedBy:   $performedBy,
                refType:       'Order',
                refId:         $orderId,
                notes:         "Deducted on order {$orderId}",
            );
        }
    }
}