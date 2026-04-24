<?php

namespace Modules\PharmaMarketing\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Platform\Contracts\Services\OrgScopeResolverInterface;
use Modules\PharmaMarketing\Models\Customer;
use Modules\PharmaMarketing\Models\CustomerContact;

class CustomerService
{
    public function __construct(
        protected OrgScopeResolverInterface $scope
    ) {}

    /**
     * Create a customer under the given org node.
     * Always stores org_id as the exact node (branch or root).
     */
    public function create(string $orgId, array $data): Customer
    {
        return DB::connection('pharma_marketing')->transaction(function () use ($orgId, $data) {
            $contacts = $data['contacts'] ?? [];
            unset($data['contacts']);

            $customer = Customer::create(array_merge($data, [
                'org_id' => $orgId,
                'code'   => $data['code'] ?? $this->generateCode($orgId),
                'status' => 'active',
            ]));

            foreach ($contacts as $i => $contact) {
                CustomerContact::create(array_merge($contact, [
                    'customer_id' => $customer->id,
                    'is_primary'  => $i === 0,
                ]));
            }

            return $customer->fresh(['contacts']);
        });
    }

    public function get(string $id): Customer
    {
        return Customer::with(['contacts', 'visits' => fn($q) => $q->limit(5)])->findOrFail($id);
    }

    /**
     * List customers with org-tree awareness.
     *
     * Root admin   → sees customers from ALL branches + root
     * Branch user  → sees customers from their branch only
     *
     * Root admin can further filter by branch:
     *   $filters['branch_id'] = '01KMQ1...'
     */
    public function list(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        $orgIds = $this->scope->scopeIds($orgId, $filters['branch_id'] ?? null);

        return Customer::whereIn('org_id', $orgIds)
            ->when(isset($filters['customer_type']), fn($q) => $q->where('customer_type', $filters['customer_type']))
            ->when(isset($filters['status']),         fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['category']),       fn($q) => $q->where('category', $filters['category']))
            ->when(isset($filters['tier']),           fn($q) => $q->where('tier', $filters['tier']))
            ->when(isset($filters['officer_id']),     fn($q) => $q->where('assigned_officer_id', $filters['officer_id']))
            ->when(isset($filters['search']),         fn($q) => $q->where(function ($q) use ($filters) {
                $q->where('name', 'ilike', "%{$filters['search']}%")
                  ->orWhere('phone', 'ilike', "%{$filters['search']}%")
                  ->orWhere('code', 'ilike', "%{$filters['search']}%");
            }))
            ->with(['contacts' => fn($q) => $q->where('is_primary', true)])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function update(string $id, array $data): Customer
    {
        $customer = Customer::findOrFail($id);
        $customer->update($data);
        return $customer->fresh(['contacts']);
    }

    public function assignOfficer(string $customerId, string $officerActorId): Customer
    {
        $customer = Customer::findOrFail($customerId);
        $customer->update(['assigned_officer_id' => $officerActorId]);
        return $customer->fresh();
    }

    public function addContact(string $customerId, array $data): CustomerContact
    {
        if ($data['is_primary'] ?? false) {
            CustomerContact::where('customer_id', $customerId)->update(['is_primary' => false]);
        }

        return CustomerContact::create(array_merge($data, ['customer_id' => $customerId]));
    }

        /**
     * Called by AuthController after customer registers via jadosoft-lite.
     * Idempotent.
     */
    public function createFromRegistration(
        string $orgId,
        string $platformUserId,
        string $displayName,
        ?string $email = null,
        ?string $phone = null,
    ): Customer {
        return DB::connection('pharma_marketing')->transaction(function () use (
            $orgId, $platformUserId, $displayName, $email, $phone
        ) {
            $existing = Customer::where('platform_user_id', $platformUserId)
                ->where('org_id', $orgId)
                ->first();

            if ($existing) return $existing;

            return Customer::create([
                'org_id'              => $orgId,
                'platform_user_id'    => $platformUserId,
                'registration_source' => 'self_registered',
                'customer_type'       => 'b2c',
                'name'                => $displayName,
                'code'                => $this->generateCode($orgId),
                'email'               => $email,
                'phone'               => $phone,
                'status'              => 'active',
                'tier'                => 'standard',
            ]);
        });
    }

    /**
     * Link an admin-created customer to a platform user by matching email.
     */
    public function linkPlatformUser(
        string $orgId,
        string $platformUserId,
        string $email
    ): ?Customer {
        $customer = Customer::where('org_id', $orgId)
            ->where('email', $email)
            ->whereNull('platform_user_id')
            ->first();

        if ($customer) {
            $customer->update([
                'platform_user_id'    => $platformUserId,
                'registration_source' => 'self_registered',
            ]);
            return $customer->fresh();
        }

        return null;
    }

    private function generateCode(string $orgId): string
    {
        // Use root org for unique code generation across the whole tree
        $rootOrgId = $this->scope->rootId($orgId);

        $orgIds = $this->scope->scopeIds($rootOrgId);

        $last = Customer::whereIn('org_id', $orgIds)
            ->whereNotNull('code')
            ->orderBy('created_at', 'desc')
            ->value('code');

        $seq = $last ? ((int) preg_replace('/[^0-9]/', '', $last)) + 1 : 1;
        return sprintf('CUST-%05d', $seq);
    }
}