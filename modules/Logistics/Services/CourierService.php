<?php

namespace Modules\Logistics\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Logistics\Models\CourierAccount;
use Modules\Logistics\Models\CourierShipment;

class CourierService
{
    public function createAccount(string $orgId, array $data): CourierAccount
    {
        // Encrypt API credentials before storing
        $data['api_key_encrypted']    = isset($data['api_key'])    ? encrypt($data['api_key'])    : null;
        $data['api_secret_encrypted'] = isset($data['api_secret']) ? encrypt($data['api_secret']) : null;
        unset($data['api_key'], $data['api_secret']);

        if ($data['is_default'] ?? false) {
            CourierAccount::where('org_id', $orgId)->update(['is_default' => false]);
        }

        return CourierAccount::create(array_merge($data, ['org_id' => $orgId]));
    }

    public function listAccounts(string $orgId): array
    {
        return CourierAccount::where('org_id', $orgId)
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    /**
     * Book a shipment with a third-party courier.
     */
    public function bookShipment(string $orgId, string $courierAccountId, array $data): CourierShipment
    {
        $account  = CourierAccount::findOrFail($courierAccountId);
        $shipment = CourierShipment::create(array_merge($data, [
            'org_id'             => $orgId,
            'courier_account_id' => $courierAccountId,
            'status'             => 'booked',
            'booked_at'          => now(),
        ]));

        // Attempt to book with courier API
        try {
            $apiResponse = $this->callCourierBookingApi($account, $shipment);
            $shipment->update([
                'tracking_number' => $apiResponse['tracking_number'] ?? null,
                'waybill_number'  => $apiResponse['waybill_number']  ?? null,
                'tracking_url'    => $apiResponse['tracking_url']    ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Courier API booking failed for shipment {$shipment->id}: " . $e->getMessage());
            // Shipment still created — tracking number can be added manually
        }

        return $shipment->fresh();
    }

    /**
     * Pull latest status from courier API and update our record.
     */
    public function syncShipmentStatus(string $shipmentId): CourierShipment
    {
        $shipment = CourierShipment::with('courierAccount')->findOrFail($shipmentId);
        $account  = $shipment->courierAccount;

        try {
            $apiResponse = $this->callCourierTrackingApi($account, $shipment->tracking_number);
            $newStatus   = $this->mapCourierStatus($account->courier, $apiResponse['status'] ?? '');

            $updates = [
                'status'        => $newStatus,
                'courier_status' => $apiResponse['status'] ?? null,
                'courier_events' => array_merge($shipment->courier_events ?? [], [$apiResponse]),
            ];

            if ($newStatus === 'delivered') $updates['delivered_at'] = now();
            if ($newStatus === 'picked_up') $updates['picked_up_at'] = now();
            if ($newStatus === 'failed')    $updates['failed_at']    = now();

            $shipment->update($updates);
        } catch (\Throwable $e) {
            Log::warning("Courier status sync failed for shipment {$shipmentId}: " . $e->getMessage());
        }

        return $shipment->fresh();
    }

    public function listShipments(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        return CourierShipment::where('org_id', $orgId)
            ->when(isset($filters['status']),   fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['courier']),  fn($q) => $q->whereHas('courierAccount', fn($q2) => $q2->where('courier', $filters['courier'])))
            ->with(['courierAccount'])
            ->orderBy('booked_at', 'desc')
            ->paginate($perPage);
    }

    // ── Private courier API adapters ───────────────────────────

    private function callCourierBookingApi(CourierAccount $account, CourierShipment $shipment): array
    {
        // Placeholder — each courier needs its own adapter
        // In production, use a Strategy pattern or individual adapter classes
        Log::debug("Courier API booking: {$account->courier} for shipment {$shipment->id}");
        return [];
    }

    private function callCourierTrackingApi(CourierAccount $account, ?string $trackingNumber): array
    {
        if (! $trackingNumber) return [];
        Log::debug("Courier API tracking: {$account->courier} / {$trackingNumber}");
        return [];
    }

    private function mapCourierStatus(string $courier, string $rawStatus): string
    {
        // Map courier-specific status strings to our normalized statuses
        $maps = [
            'dhl'  => ['DELIVERED' => 'delivered', 'IN_TRANSIT' => 'in_transit', 'PICKED_UP' => 'picked_up'],
            'g4s'  => ['delivered' => 'delivered', 'in_transit' => 'in_transit'],
            'sendy' => ['delivered' => 'delivered', 'picked_up' => 'picked_up'],
        ];

        return $maps[$courier][$rawStatus] ?? 'in_transit';
    }
}
