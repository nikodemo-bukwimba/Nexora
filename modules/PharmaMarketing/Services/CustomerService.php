<?php
// FILE: modules/PharmaMarketing/Services/CustomerService.php
// PATH: modules/PharmaMarketing/Services/CustomerService.php
//
// CHANGES:
//   1. create() — always stores org_id as ROOT org (never branch)
//                 stores home_branch_id if a branch was specified
//   2. list()   — always queries by root org; branch filter becomes
//                 an officer/visit filter, not an org_id filter
//   3. createFromRegistration() — stores root org, not branch
//   4. linkPlatformUser() — searches by root org
//   All other methods unchanged.

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
     * Create a customer.
     *
     * CHANGE: org_id is always the ROOT org.
     * home_branch_id records which branch created them (for reporting).
     * This allows any branch to serve the customer.
     */
    public function create(string $orgId, array $data): Customer
    {
        return DB::connection('pharma_marketing')->transaction(function () use ($orgId, $data) {
            // ── CHANGE: resolve root org ──────────────────────────────────
            $rootOrgId = $this->scope->rootId($orgId);
            $isBranch  = $rootOrgId !== $orgId;
            // ─────────────────────────────────────────────────────────────

            $contacts = $data['contacts'] ?? [];
            unset($data['contacts']);

            $customer = Customer::create(array_merge($data, [
                'org_id'         => $rootOrgId,          // ← always root
                'home_branch_id' => $isBranch ? $orgId : null, // ← which branch created them
                'code'           => $data['code'] ?? $this->generateCode($rootOrgId),
                'status'         => 'active',
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
     * List customers.
     *
     * CHANGE: always queries root org — all branches see all customers.
     * Branch admins/officers filter by their assigned customers via officer_id.
     * No more branch-scoping via org_id tree walk.
     */
    public function list(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        // ── CHANGE: always use root org ───────────────────────────────────
        $rootOrgId = $this->scope->rootId($orgId);
        // ─────────────────────────────────────────────────────────────────

        return Customer::where('org_id', $rootOrgId)
            ->when(isset($filters['customer_type']), fn($q) => $q->where('customer_type', $filters['customer_type']))
            ->when(isset($filters['status']),         fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['category']),       fn($q) => $q->where('category', $filters['category']))
            ->when(isset($filters['tier']),           fn($q) => $q->where('tier', $filters['tier']))
            ->when(isset($filters['officer_id']),     fn($q) => $q->where('assigned_officer_id', $filters['officer_id']))
            // ── CHANGE: branch_id now filters by home_branch_id (optional reporting filter)
            ->when(isset($filters['branch_id']),      fn($q) => $q->where('home_branch_id', $filters['branch_id']))
            ->when(isset($filters['search']),         fn($q) => $q->where(function ($q) use ($filters) {
                $q->where('name',  'ilike', "%{$filters['search']}%")
                  ->orWhere('phone', 'ilike', "%{$filters['search']}%")
                  ->orWhere('code',  'ilike', "%{$filters['search']}%");
            }))
            ->with(['contacts' => fn($q) => $q->where('is_primary', true)])
            ->orderBy('name')
            ->paginate($perPage);
    }

public function update(string $id, array $data): Customer
{
    $customer = Customer::findOrFail($id);

    // Extract contact fields before updating the customer row
    $contactName  = $data['contact_name']  ?? null;
    $contactPhone = $data['contact_phone'] ?? null;
    $contactRole  = $data['contact_role']  ?? null;
    unset($data['contact_name'], $data['contact_phone'], $data['contact_role']);

    $customer->update($data);

    // Upsert primary contact if any contact field was provided
    if ($contactName !== null || $contactPhone !== null || $contactRole !== null) {
        $existing = CustomerContact::where('customer_id', $customer->id)
            ->where('is_primary', true)
            ->first();

        $contactData = array_filter([
            'name'  => $contactName,
            'phone' => $contactPhone,
            'role'  => $contactRole,
        ], fn($v) => $v !== null);

        try {
            if ($existing) {
                $existing->update($contactData);
            } else {
                CustomerContact::create(array_merge($contactData, [
                    'customer_id' => $customer->id,
                    'name'        => $contactName ?? '',
                    'is_primary'  => true,
                ]));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('CustomerContact create/update failed', [
                'customer_id'  => $customer->id,
                'contact_data' => $contactData,
                'error'        => $e->getMessage(),
            ]);
            throw $e;
        }
    }

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
     * CHANGE: always uses root org.
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
            // ── CHANGE: always root org ───────────────────────────────────
            $rootOrgId = $this->scope->rootId($orgId);
            // ─────────────────────────────────────────────────────────────

            $existing = Customer::where('platform_user_id', $platformUserId)
                ->where('org_id', $rootOrgId)
                ->first();

            if ($existing) return $existing;

            return Customer::create([
                'org_id'              => $rootOrgId,   // ← always root
                'platform_user_id'    => $platformUserId,
                'registration_source' => 'self_registered',
                'customer_type'       => 'b2c',
                'name'                => $displayName,
                'code'                => $this->generateCode($rootOrgId),
                'email'               => $email,
                'phone'               => $phone,
                'status'              => 'active',
                'tier'                => 'standard',
            ]);
        });
    }

    /**
     * Link an admin-created customer to a platform user by matching email.
     * CHANGE: searches by root org so branch-created customers are found too.
     */
    public function linkPlatformUser(
        string $orgId,
        string $platformUserId,
        string $email
    ): ?Customer {
        // ── CHANGE: use root org so branch-created customers are found ────
        $rootOrgId = $this->scope->rootId($orgId);
        // ─────────────────────────────────────────────────────────────────

        $customer = Customer::where('org_id', $rootOrgId)
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
        $rootOrgId = $this->scope->rootId($orgId);

        $last = Customer::where('org_id', $rootOrgId)
            ->whereNotNull('code')
            ->orderBy('created_at', 'desc')
            ->value('code');

        $seq = $last ? ((int) preg_replace('/[^0-9]/', '', $last)) + 1 : 1;
        return sprintf('CUST-%05d', $seq);
    }
}
