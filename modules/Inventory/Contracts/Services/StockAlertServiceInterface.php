<?php

namespace Modules\Inventory\Contracts\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Inventory\Models\StockAlert;

interface StockAlertServiceInterface
{
    public function checkAndCreateAlerts(string $batchId): void;
    public function listAlerts(string $orgId, array $filters, int $perPage): LengthAwarePaginator;
    public function acknowledge(string $alertId, string $acknowledgedBy): StockAlert;
    public function resolve(string $alertId): StockAlert;
}
