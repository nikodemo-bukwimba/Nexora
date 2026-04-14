<?php

namespace Modules\Logistics\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Logistics\Models\DeliveryProof;
use Modules\Logistics\Models\DeliveryRun;
use Modules\Logistics\Models\DeliveryStop;
use Modules\Logistics\Models\StopStatusLog;

class DeliveryRunService
{
    public function __construct(
        protected CostCalculationService $costs
    ) {}

    /**
     * Create a delivery run with stops.
     */
    public function create(string $orgId, string $dispatchedBy, array $data): DeliveryRun
    {
        return DB::connection('logistics')->transaction(function () use ($orgId, $dispatchedBy, $data) {
            $stops = $data['stops'] ?? [];
            unset($data['stops']);

            $run = DeliveryRun::create(array_merge($data, [
                'org_id'        => $orgId,
                'dispatched_by' => $dispatchedBy,
                'run_number'    => $this->generateRunNumber(),
                'status'        => 'draft',
                'total_stops'   => count($stops),
            ]));

            foreach ($stops as $i => $stopData) {
                $cost = $this->costs->calculateForStop($orgId, $stopData);
                DeliveryStop::create(array_merge($stopData, [
                    'run_id'        => $run->id,
                    'org_id'        => $orgId,
                    'stop_sequence' => $stopData['stop_sequence'] ?? $i + 1,
                    'status'        => 'pending',
                    'delivery_cost' => $cost,
                    'currency'      => $data['currency'] ?? config('logistics.default_currency'),
                ]));
            }

            return $run->fresh(['stops', 'driver', 'vehicle']);
        });
    }

    public function get(string $id): DeliveryRun
    {
        return DeliveryRun::with(['stops.proof', 'stops.statusLogs', 'driver', 'vehicle'])->findOrFail($id);
    }

    public function list(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        return DeliveryRun::where('org_id', $orgId)
            ->when(isset($filters['driver_id']),  fn($q) => $q->where('driver_id', $filters['driver_id']))
            ->when(isset($filters['status']),      fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date']),        fn($q) => $q->where('scheduled_date', $filters['date']))
            ->with(['driver', 'vehicle'])
            ->orderBy('scheduled_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Dispatch a run — assign driver + vehicle and set status to dispatched.
     */
    public function dispatch(string $runId, string $driverId, string $vehicleId): DeliveryRun
    {
        return DB::connection('logistics')->transaction(function () use ($runId, $driverId, $vehicleId) {
            $run = DeliveryRun::where('status', 'draft')->findOrFail($runId);

            $run->update([
                'driver_id'      => $driverId,
                'vehicle_id'     => $vehicleId,
                'status'         => 'dispatched',
                'dispatched_at'  => now(),
            ]);

            // Mark driver as on_run
            \Modules\Logistics\Models\Driver::findOrFail($driverId)
                ->update(['availability' => 'on_run']);

            // Set all stops to pending explicitly
            $run->stops()->update(['status' => 'pending']);

            return $run->fresh(['stops', 'driver', 'vehicle']);
        });
    }

    /**
     * Driver starts the run from their mobile app.
     */
    public function startRun(string $runId, string $driverActorId): DeliveryRun
    {
        $driver = \Modules\Logistics\Models\Driver::where('actor_id', $driverActorId)->firstOrFail();
        $run    = DeliveryRun::where('id', $runId)
            ->where('driver_id', $driver->id)
            ->where('status', 'dispatched')
            ->firstOrFail();

        $run->update(['status' => 'in_progress', 'started_at' => now()]);

        return $run->fresh(['stops']);
    }

    /**
     * Update a stop's status. Called by driver on mobile.
     * Valid transitions: pending→en_route, en_route→arrived, arrived→delivered|failed
     */
    public function updateStopStatus(string $stopId, string $changedByActorId, string $newStatus, array $data = []): DeliveryStop
    {
        return DB::connection('logistics')->transaction(function () use ($stopId, $changedByActorId, $newStatus, $data) {

            $stop = DeliveryStop::lockForUpdate()->findOrFail($stopId);
            $fromStatus = $stop->status;

            $validTransitions = [
                'pending'    => ['en_route'],
                'en_route'   => ['arrived'],
                'arrived'    => ['delivered', 'failed'],
                'failed'     => ['rescheduled'],
            ];

            if (! in_array($newStatus, $validTransitions[$fromStatus] ?? [])) {
                throw new \RuntimeException("Invalid status transition: {$fromStatus} → {$newStatus}");
            }

            $updates = ['status' => $newStatus];

            if ($newStatus === 'arrived')   $updates['arrived_at']   = now();
            if ($newStatus === 'delivered') $updates['delivered_at'] = now();
            if ($newStatus === 'failed')    $updates['failed_at']    = now();

            if ($newStatus === 'delivered') {
                $updates['delivery_latitude']  = $data['latitude'] ?? null;
                $updates['delivery_longitude'] = $data['longitude'] ?? null;
            }

            if ($newStatus === 'failed') {
                $updates['failure_reason'] = $data['failure_reason'] ?? null;
                $updates['failure_notes']  = $data['failure_notes'] ?? null;
                $updates['rescheduled_date'] = $data['rescheduled_date'] ?? null;
            }

            $stop->update($updates);

            // Immutable status log entry
            StopStatusLog::create([
                'stop_id'     => $stopId,
                'from_status' => $fromStatus,
                'to_status'   => $newStatus,
                'changed_by'  => $changedByActorId,
                'latitude'    => $data['latitude'] ?? null,
                'longitude'   => $data['longitude'] ?? null,
                'notes'       => $data['notes'] ?? null,
            ]);

            // Update Commerce order status if linked
            if ($stop->order_id) {
                $this->syncCommerceOrderStatus($stop->order_id, $newStatus);
            }

            // Check if entire run is now complete
            $this->checkRunCompletion($stop->run_id);

            return $stop->fresh(['proof', 'statusLogs']);
        });
    }

    /**
     * Record proof of delivery for a stop.
     */
    public function recordProof(string $stopId, string $capturedByActorId, array $data): DeliveryProof
    {
        $stop = DeliveryStop::findOrFail($stopId);

        if (! $stop->isDelivered() && ! $stop->isEnRoute() && $stop->status !== 'arrived') {
            throw new \RuntimeException('Proof can only be recorded for stops that are arrived or delivered.');
        }

        return DeliveryProof::updateOrCreate(
            ['stop_id' => $stopId],
            array_merge($data, [
                'captured_by' => $capturedByActorId,
                'captured_at' => now(),
            ])
        );
    }

    /**
     * Add a new stop to an existing draft run.
     */
    public function addStop(string $runId, string $orgId, array $data): DeliveryStop
    {
        $run = DeliveryRun::where('status', 'draft')->findOrFail($runId);

        $maxSeq = $run->stops()->max('stop_sequence') ?? 0;
        $cost   = $this->costs->calculateForStop($orgId, $data);

        $stop = DeliveryStop::create(array_merge($data, [
            'run_id'        => $runId,
            'org_id'        => $orgId,
            'stop_sequence' => $data['stop_sequence'] ?? $maxSeq + 1,
            'status'        => 'pending',
            'delivery_cost' => $cost,
        ]));

        $run->increment('total_stops');

        return $stop;
    }

    /**
     * Reorder stops within a run (drag-and-drop resequencing).
     */
    public function reorderStops(string $runId, array $stopSequences): void
    {
        // stopSequences = [['stop_id' => '01...', 'sequence' => 1], ...]
        DB::connection('logistics')->transaction(function () use ($runId, $stopSequences) {
            foreach ($stopSequences as $item) {
                DeliveryStop::where('run_id', $runId)
                    ->where('id', $item['stop_id'])
                    ->update(['stop_sequence' => $item['sequence']]);
            }
        });
    }

    // ── Private helpers ────────────────────────────────────────

    private function syncCommerceOrderStatus(string $orderId, string $stopStatus): void
    {
        // Update Commerce order fulfillment based on stop status
        // Uses soft FK — reads commerce.orders via cross-schema query
        try {
            $mapping = [
                'en_route'  => 'shipped',
                'delivered' => 'delivered',
            ];

            if (! isset($mapping[$stopStatus])) return;

            $commerceStatus = $mapping[$stopStatus];

            // Direct DB update across schemas — safe for foundation modules
            DB::connection('commerce')->table('orders')
                ->where('id', $orderId)
                ->update([
                    'status'     => $commerceStatus,
                    'updated_at' => now(),
                ]);

            if ($commerceStatus === 'delivered') {
                DB::connection('commerce')->table('order_fulfillments')
                    ->where('order_id', $orderId)
                    ->update([
                        'status'       => 'delivered',
                        'delivered_at' => now(),
                        'updated_at'   => now(),
                    ]);
            }
        } catch (\Throwable $e) {
            // Non-fatal — log but don't fail the stop update
            \Illuminate\Support\Facades\Log::warning("Failed to sync commerce order {$orderId}: " . $e->getMessage());
        }
    }

    private function checkRunCompletion(string $runId): void
    {
        $run   = DeliveryRun::find($runId);
        if (! $run || $run->isCompleted()) return;

        $stops          = $run->stops()->get();
        $allDone        = $stops->every(fn($s) => in_array($s->status, ['delivered', 'failed', 'rescheduled', 'cancelled']));
        $deliveredCount = $stops->where('status', 'delivered')->count();
        $failedCount    = $stops->where('status', 'failed')->count();

        if ($allDone) {
            $status = $failedCount > 0 ? 'partially_completed' : 'completed';
            $run->update([
                'status'          => $status,
                'completed_at'    => now(),
                'delivered_count' => $deliveredCount,
                'failed_count'    => $failedCount,
            ]);

            // Free up the driver
            if ($run->driver_id) {
                \Modules\Logistics\Models\Driver::where('id', $run->driver_id)
                    ->update(['availability' => 'online']);
            }
        } else {
            // Update running counts
            $run->update([
                'delivered_count' => $deliveredCount,
                'failed_count'    => $failedCount,
            ]);
        }
    }

    private function generateRunNumber(): string
    {
        $year = now()->year;
        $last = DeliveryRun::where('run_number', 'like', "RUN-{$year}-%")
            ->orderBy('created_at', 'desc')
            ->value('run_number');
        $seq  = $last ? ((int) substr($last, -6)) + 1 : 1;
        return sprintf('RUN-%d-%06d', $year, $seq);
    }
}
