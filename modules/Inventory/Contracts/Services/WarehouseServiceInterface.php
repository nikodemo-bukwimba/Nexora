<?php

namespace Modules\Inventory\Contracts\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Inventory\Models\Warehouse;

interface WarehouseServiceInterface
{
    public function create(string $orgId, array $data): Warehouse;
    public function get(string $id): Warehouse;
    public function listForOrg(string $orgId, int $perPage): LengthAwarePaginator;
    public function update(string $id, array $data): Warehouse;
    public function deactivate(string $id): Warehouse;
}
