<?php

namespace Modules\PharmaMarketing\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Platform\Contracts\Services\OrgScopeResolverInterface;
use Modules\PharmaMarketing\Models\Customer;
use Modules\PharmaMarketing\Models\ProductUpdate;
use Modules\PharmaMarketing\Models\ProductUpdateDelivery;
use Modules\PharmaMarketing\Jobs\SendProductUpdateToCustomer;

class ProductUpdateService
{
    public function __construct(
        protected OrgScopeResolverInterface $scope
    ) {}

    public function create(string $orgId, string $createdBy, array $data): ProductUpdate
    {
        return ProductUpdate::create(array_merge(
            array_intersect_key($data, array_flip([
                'title', 'body', 'update_type', 'target_segment', 'target_filters',
                'send_in_app', 'send_whatsapp', 'send_sms',
                'product_ids', 'media_url', 'media_type',
                'scheduled_at', 'start_date', 'end_date',
                'discount_percentage',
            ])),
            [
                'org_id'     => $orgId,
                'created_by' => $createdBy,
                'status'     => 'draft',
            ]
        ));
    }

    public function get(string $id): ProductUpdate
    {
        return ProductUpdate::with(['deliveries'])->findOrFail($id);
    }

    /**
     * List product updates with org-tree awareness.
     *
     * Root admin  → sees updates from ALL branches
     * Branch user → sees updates from their branch only
     */
    public function list(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        $orgIds = $this->scope->scopeIds($orgId, $filters['branch_id'] ?? null);

        // ── FIX ───────────────────────────────────────────────────────────────
        // Promotions are created at root org level and apply tree-wide.
        // When a branch manager requests product-updates, $orgIds only contains
        // their branch ID — missing the root org where promotions actually live.
        // Always include the root org so branch users see root-level promotions.
        $rootOrgId = \Modules\Platform\Models\Organization::find($orgId)?->root_org_id ?? $orgId;
        $orgIds    = collect($orgIds)->push($rootOrgId)->unique()->values()->all();
        // ── END FIX ───────────────────────────────────────────────────────────

        return ProductUpdate::whereIn('org_id', $orgIds)
            ->when(isset($filters['status']),      fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['update_type']), fn($q) => $q->where('update_type', $filters['update_type']))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function patch(string $id, array $data): ProductUpdate
    {
        $update = ProductUpdate::findOrFail($id);
        $update->update(array_intersect_key($data, array_flip([
            'title', 'body', 'update_type', 'target_segment', 'target_filters',
            'send_in_app', 'send_whatsapp', 'send_sms',
            'product_ids', 'media_url', 'media_type',
            'status', 'scheduled_at',
            'start_date', 'end_date',
            'discount_percentage',
        ])));
        return $update->fresh();
    }

    public function publish(string $updateId): ProductUpdate
    {
        $update = ProductUpdate::where('status', 'draft')->findOrFail($updateId);

        $customers = $this->resolveTargetCustomers($update);
        $total     = $customers->count();

        $update->update([
            'status'           => 'sending',
            'total_recipients' => $total,
        ]);

        foreach ($customers as $customer) {
            $channels = [];
            if ($update->send_in_app && $customer->receives_in_app)     $channels[] = 'in_app';
            if ($update->send_whatsapp && $customer->receives_whatsapp) $channels[] = 'whatsapp';
            if ($update->send_sms && $customer->receives_sms)           $channels[] = 'sms';

            foreach ($channels as $channel) {
                $delivery = ProductUpdateDelivery::create([
                    'product_update_id' => $updateId,
                    'customer_id'       => $customer->id,
                    'channel'           => $channel,
                    'status'            => 'pending',
                    'recipient_address' => $this->getRecipientAddress($customer, $channel),
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
            'total'      => $deliveries->count(),
            'sent'       => $deliveries->where('status', 'sent')->count(),
            'delivered'  => $deliveries->where('status', 'delivered')->count(),
            'read'       => $deliveries->where('status', 'read')->count(),
            'failed'     => $deliveries->where('status', 'failed')->count(),
            'by_channel' => $deliveries->groupBy('channel')->map->count(),
        ];
    }

    private function resolveTargetCustomers(ProductUpdate $update)
    {
        $orgIds  = $this->scope->scopeIds($update->org_id);
        $query   = Customer::whereIn('org_id', $orgIds)->where('status', 'active');
        $segment = $update->target_segment;
        $filters = $update->target_filters ?? [];

        if ($segment === 'b2b') $query->where('customer_type', 'b2b');
        if ($segment === 'b2c') $query->where('customer_type', 'b2c');

        if (str_starts_with($segment ?? '', 'tier:'))     $query->where('tier', substr($segment, 5));
        if (str_starts_with($segment ?? '', 'category:')) $query->where('category', substr($segment, 9));
        if (!empty($filters['county']))                   $query->where('county', $filters['county']);

        return $query->get();
    }

    private function getRecipientAddress(Customer $customer, string $channel): ?string
    {
        return match ($channel) {
            'whatsapp' => $customer->whatsapp_number ?? $customer->phone,
            'sms'      => $customer->phone,
            'in_app'   => null,
            default    => null,
        };
    }
}