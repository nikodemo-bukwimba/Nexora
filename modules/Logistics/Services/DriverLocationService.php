<?php

namespace Modules\Logistics\Services;

use Illuminate\Support\Facades\DB;
use Modules\Logistics\Models\Driver;
use Modules\Logistics\Models\DriverLastPosition;
use Modules\Logistics\Models\DriverLocation;
use Modules\Logistics\Models\DeliveryStop;

class DriverLocationService
{
    /**
     * Ping: driver sends their current GPS position.
     * Appends to location log and upserts last-known position.
     *
     * @param  string $driverActorId  actor_id of the authenticated driver
     * @param  array  $data           {latitude, longitude, accuracy_meters?, speed_kmh?,
     *                                 heading_degrees?, source?, recorded_at?}
     */
    public function ping(string $driverActorId, array $data): DriverLastPosition
    {
        $driver = Driver::where('actor_id', $driverActorId)->firstOrFail();

        // Resolve active run and current stop
        $activeRun  = $driver->activeRun();
        $runId      = $activeRun?->id;
        $currentStop = null;

        if ($activeRun) {
            $currentStop = $activeRun->stops()
                ->whereNotIn('status', ['delivered', 'failed', 'rescheduled', 'cancelled'])
                ->orderBy('stop_sequence')
                ->first();
        }

        $locationData = [
            'driver_id'        => $driver->id,
            'run_id'           => $runId,
            'stop_id'          => $currentStop?->id,
            'latitude'         => $data['latitude'],
            'longitude'        => $data['longitude'],
            'accuracy_meters'  => $data['accuracy_meters'] ?? null,
            'speed_kmh'        => $data['speed_kmh'] ?? null,
            'heading_degrees'  => $data['heading_degrees'] ?? null,
            'source'           => $data['source'] ?? 'gps',
            'recorded_at'      => $data['recorded_at'] ?? now(),
        ];

        // Append to history log
        DriverLocation::create($locationData);

        // Upsert last-known position (single row per driver — fast lookup)
        DriverLastPosition::updateOrCreate(
            ['driver_id' => $driver->id],
            array_merge($locationData, ['updated_at' => now()])
        );

        // Update driver last_seen_at
        $driver->update(['last_seen_at' => now()]);

        return DriverLastPosition::find($driver->id);
    }

    /**
     * Get current position of a driver.
     * Used by admin/dispatch dashboard.
     */
    public function getCurrentPosition(string $driverId): ?DriverLastPosition
    {
        return DriverLastPosition::find($driverId);
    }

    /**
     * Get the last known position of the driver assigned to a given order's stop.
     * Used by the customer tracking endpoint to show driver on map.
     *
     * Returns null if:
     *   - Order is not in an active delivery stop
     *   - Driver position is stale (> 10 minutes)
     *   - Stop is already delivered/failed
     */
    public function getPositionForOrder(string $orderId): ?array
    {
        $stop = DeliveryStop::where('order_id', $orderId)
            ->whereIn('status', ['en_route', 'arrived'])
            ->with('run.driver')
            ->latest()
            ->first();

        if (! $stop || ! $stop->run?->driver) {
            return null;
        }

        $driver   = $stop->run->driver;
        $position = DriverLastPosition::find($driver->id);

        if (! $position) return null;

        // Stale if last ping > 10 minutes ago
        if ($position->recorded_at?->diffInMinutes(now()) > 10) {
            return null;
        }

        return [
            'latitude'       => (float) $position->latitude,
            'longitude'      => (float) $position->longitude,
            'accuracy_meters' => $position->accuracy_meters ? (float) $position->accuracy_meters : null,
            'speed_kmh'      => $position->speed_kmh ? (float) $position->speed_kmh : null,
            'heading_degrees' => $position->heading_degrees ? (float) $position->heading_degrees : null,
            'recorded_at'    => $position->recorded_at,
            'driver_name'    => $driver->name,
            'driver_phone'   => $driver->phone,
            'is_stale'       => false,
        ];
    }

    /**
     * Get the last N location history points for a driver on a run.
     * Used for breadcrumb trail on admin dashboard.
     */
    public function getLocationHistory(string $driverId, string $runId, int $limit = 50): array
    {
        return DriverLocation::where('driver_id', $driverId)
            ->where('run_id', $runId)
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($loc) => [
                'latitude'    => (float) $loc->latitude,
                'longitude'   => (float) $loc->longitude,
                'speed_kmh'   => $loc->speed_kmh ? (float) $loc->speed_kmh : null,
                'heading'     => $loc->heading_degrees ? (float) $loc->heading_degrees : null,
                'recorded_at' => $loc->recorded_at,
            ])
            ->toArray();
    }

    /**
     * All active driver positions for an org — for dispatch board map view.
     */
    public function getAllActivePositions(string $orgId): array
    {
        $drivers = Driver::where('org_id', $orgId)
            ->whereIn('availability', ['on_run', 'online'])
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();

        if (empty($drivers)) return [];

        // Only positions updated in the last 30 minutes
        $cutoff = now()->subMinutes(30);

        return DriverLastPosition::whereIn('driver_id', $drivers)
            ->where('updated_at', '>=', $cutoff)
            ->get()
            ->map(fn($pos) => [
                'driver_id'      => $pos->driver_id,
                'run_id'         => $pos->run_id,
                'stop_id'        => $pos->stop_id,
                'latitude'       => (float) $pos->latitude,
                'longitude'      => (float) $pos->longitude,
                'speed_kmh'      => $pos->speed_kmh ? (float) $pos->speed_kmh : null,
                'heading_degrees' => $pos->heading_degrees ? (float) $pos->heading_degrees : null,
                'recorded_at'    => $pos->recorded_at,
                'is_stale'       => $pos->recorded_at?->diffInMinutes(now()) > 5,
            ])
            ->toArray();
    }
}