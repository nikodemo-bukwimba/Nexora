<?php

namespace Modules\Inventory\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Inventory\Contracts\Services\StockAlertServiceInterface;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\StockAlert;

class StockAlertService implements StockAlertServiceInterface
{
    public function checkAndCreateAlerts(string $batchId): void
    {
        $batch     = InventoryBatch::find($batchId);
        if (! $batch) return;

        $threshold = config('inventory.alerts.default_low_stock_threshold', 10);
        $expiryWarningDays = config('inventory.alerts.default_expiry_warning_days', 30);

        // Out of stock alert
        if ($batch->quantity_available <= 0) {
            $this->createOrUpdateAlert($batch, 'out_of_stock', 0, $batch->quantity_available, "Product is out of stock.");
        }
        // Low stock alert
        elseif ($batch->quantity_available <= $threshold) {
            $this->createOrUpdateAlert($batch, 'low_stock', $threshold, $batch->quantity_available, "Stock is below threshold of {$threshold}.");
        }

        // Expiry alerts
        if ($batch->expires_at) {
            if ($batch->expires_at->isPast()) {
                $this->createOrUpdateAlert($batch, 'expired', 0, 0, "Batch has expired.");
            } elseif ($batch->isNearExpiry($expiryWarningDays)) {
                $daysLeft = (int) now()->diffInDays($batch->expires_at);
                $this->createOrUpdateAlert($batch, 'near_expiry', $expiryWarningDays, $daysLeft, "Batch expires in {$daysLeft} day(s).");
            }
        }
    }

    public function listAlerts(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        return StockAlert::where('org_id', $orgId)
            ->when(isset($filters['type']),   fn($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['warehouse_id']), fn($q) => $q->where('warehouse_id', $filters['warehouse_id']))
            ->with(['warehouse', 'batch'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function acknowledge(string $alertId, string $acknowledgedBy): StockAlert
    {
        $alert = StockAlert::where('status', 'active')->findOrFail($alertId);
        $alert->update([
            'status'           => 'acknowledged',
            'acknowledged_by'  => $acknowledgedBy,
            'acknowledged_at'  => now(),
        ]);
        return $alert->fresh();
    }

    public function resolve(string $alertId): StockAlert
    {
        $alert = StockAlert::findOrFail($alertId);
        $alert->update(['status' => 'resolved']);
        return $alert->fresh();
    }

    private function createOrUpdateAlert(InventoryBatch $batch, string $type, int $threshold, int $currentValue, string $message): void
    {
        StockAlert::updateOrCreate(
            [
                'batch_id'   => $batch->id,
                'org_id'     => $batch->org_id,
                'type'       => $type,
                'status'     => 'active',
            ],
            [
                'warehouse_id'   => $batch->warehouse_id,
                'product_id'     => $batch->product_id,
                'threshold'      => $threshold,
                'current_value'  => $currentValue,
                'message'        => $message,
            ]
        );
    }
}
