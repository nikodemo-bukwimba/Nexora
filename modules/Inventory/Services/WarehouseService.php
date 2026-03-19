<?php

namespace Modules\Inventory\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Inventory\Contracts\Services\WarehouseServiceInterface;
use Modules\Inventory\Models\Warehouse;

class WarehouseService implements WarehouseServiceInterface
{
    public function create(string $orgId, array $data): Warehouse
    {
        return Warehouse::create(array_merge($data, ['org_id' => $orgId, 'status' => 'active']));
    }

    public function get(string $id): Warehouse
    {
        return Warehouse::withCount(['batches as active_batches_count' => fn($q) => $q->where('status', 'active')])
            ->findOrFail($id);
    }

    public function listForOrg(string $orgId, int $perPage): LengthAwarePaginator
    {
        return Warehouse::where('org_id', $orgId)
            ->withCount(['batches as active_batches_count' => fn($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function update(string $id, array $data): Warehouse
    {
        $warehouse = Warehouse::findOrFail($id);
        $allowed   = array_intersect_key($data, array_flip(['name', 'code', 'type', 'address', 'city', 'country', 'settings']));
        $warehouse->update($allowed);
        return $warehouse->fresh();
    }

    public function deactivate(string $id): Warehouse
    {
        $warehouse = Warehouse::findOrFail($id);

        $hasStock = $warehouse->batches()
            ->where('status', 'active')
            ->where('quantity_available', '>', 0)
            ->exists();

        if ($hasStock) {
            throw new \RuntimeException('Cannot deactivate a warehouse with active stock. Transfer or adjust stock first.');
        }

        $warehouse->update(['status' => 'inactive']);
        return $warehouse->fresh();
    }
}
