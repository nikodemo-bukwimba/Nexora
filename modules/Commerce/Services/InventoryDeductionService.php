<?php

namespace Modules\Commerce\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Contracts\Services\InventoryServiceInterface;

class InventoryDeductionService
{
    public function __construct(
        protected InventoryServiceInterface $inventory,
    ) {}

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

        // ── Step 3: FEFO drain ────────────────────────────────
        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $batchAvailable = $batch->quantity_available - $batch->quantity_reserved;
            if ($batchAvailable <= 0) continue;

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