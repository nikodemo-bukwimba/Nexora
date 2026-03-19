<?php

namespace Modules\PharmaMarketing\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\PharmaMarketing\Models\Customer;
use Modules\PharmaMarketing\Models\ProductUpdate;
use Modules\PharmaMarketing\Models\ProductUpdateDelivery;
use Modules\PharmaMarketing\Jobs\SendProductUpdateToCustomer;

class ProductUpdateService
{
    public function create(string $orgId, string $createdBy, array $data): ProductUpdate
    {
        return ProductUpdate::create(array_merge($data, [
            'org_id'     => $orgId,
            'created_by' => $createdBy,
            'status'     => 'draft',
        ]));
    }

    public function get(string $id): ProductUpdate
    {
        return ProductUpdate::with(['deliveries'])->findOrFail($id);
    }

    public function list(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        return ProductUpdate::where('org_id', $orgId)
            ->when(isset($filters['status']),      fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['update_type']), fn($q) => $q->where('update_type', $filters['update_type']))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Publish a product update — resolve target customers and dispatch delivery jobs.
     */
    public function publish(string $updateId): ProductUpdate
    {
        $update = ProductUpdate::where('status', 'draft')->findOrFail($updateId);

        $customers = $this->resolveTargetCustomers($update);
        $total     = $customers->count();

        $update->update([
            'status'           => 'sending',
            'total_recipients' => $total,
        ]);

        // Create delivery records and dispatch jobs
        foreach ($customers as $customer) {
            $channels = [];
            if ($update->send_in_app && $customer->receives_in_app)    $channels[] = 'in_app';
            if ($update->send_whatsapp && $customer->receives_whatsapp) $channels[] = 'whatsapp';
            if ($update->send_sms && $customer->receives_sms)           $channels[] = 'sms';

            foreach ($channels as $channel) {
                $delivery = ProductUpdateDelivery::create([
                    'product_update_id'  => $updateId,
                    'customer_id'        => $customer->id,
                    'channel'            => $channel,
                    'status'             => 'pending',
                    'recipient_address'  => $this->getRecipientAddress($customer, $channel),
                ]);

                SendProductUpdateToCustomer::dispatch($delivery, $update, $customer);
            }
        }

        $update->update(['status' => 'sent', 'sent_at' => now()]);
        return $update->fresh();
    }

    public function getDeliveryStats(string $updateId): array
    {
        $deliveries = ProductUpdateDelivery::where('product_update_id', $updateId)->get();

        return [
            'total'     => $deliveries->count(),
            'sent'      => $deliveries->where('status', 'sent')->count(),
            'delivered' => $deliveries->where('status', 'delivered')->count(),
            'read'      => $deliveries->where('status', 'read')->count(),
            'failed'    => $deliveries->where('status', 'failed')->count(),
            'by_channel' => $deliveries->groupBy('channel')->map->count(),
        ];
    }

    private function resolveTargetCustomers(ProductUpdate $update)
    {
        $query = Customer::where('org_id', $update->org_id)->where('status', 'active');

        $segment = $update->target_segment;
        $filters = $update->target_filters ?? [];

        if ($segment === 'b2b') $query->where('customer_type', 'b2b');
        if ($segment === 'b2c') $query->where('customer_type', 'b2c');

        if (str_starts_with($segment, 'tier:')) {
            $query->where('tier', substr($segment, 5));
        }

        if (str_starts_with($segment, 'category:')) {
            $query->where('category', substr($segment, 9));
        }

        if (! empty($filters['county'])) {
            $query->where('county', $filters['county']);
        }

        return $query->get();
    }

    private function getRecipientAddress(Customer $customer, string $channel): ?string
    {
        return match ($channel) {
            'whatsapp' => $customer->whatsapp_number ?? $customer->phone,
            'sms'      => $customer->phone,
            'in_app'   => null, // resolved from platform actor_id at delivery time
            default    => null,
        };
    }
}
