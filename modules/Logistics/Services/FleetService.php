<?php

namespace Modules\Logistics\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Logistics\Models\Driver;
use Modules\Logistics\Models\Vehicle;

class FleetService
{
    // ── Vehicles ───────────────────────────────────────────────

    public function createVehicle(string $orgId, array $data): Vehicle
    {
        return Vehicle::create(array_merge($data, ['org_id' => $orgId, 'status' => 'active']));
    }

    public function listVehicles(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        return Vehicle::where('org_id', $orgId)
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['type']),   fn($q) => $q->where('type', $filters['type']))
            ->orderBy('registration')
            ->paginate($perPage);
    }

    public function updateVehicle(string $id, array $data): Vehicle
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->update($data);
        return $vehicle->fresh();
    }

    // ── Drivers ────────────────────────────────────────────────

    public function createDriver(string $orgId, array $data): Driver
    {
        return Driver::create(array_merge($data, [
            'org_id'       => $orgId,
            'status'       => 'active',
            'availability' => 'offline',
        ]));
    }

    public function listDrivers(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        return Driver::where('org_id', $orgId)
            ->when(isset($filters['status']),       fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['availability']), fn($q) => $q->where('availability', $filters['availability']))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function updateDriverAvailability(string $driverActorId, string $availability): Driver
    {
        $driver = Driver::where('actor_id', $driverActorId)->firstOrFail();
        $driver->update([
            'availability' => $availability,
            'last_seen_at' => now(),
        ]);
        return $driver->fresh();
    }

    public function getDriverByActorId(string $actorId): Driver
    {
        return Driver::where('actor_id', $actorId)->firstOrFail();
    }
}
