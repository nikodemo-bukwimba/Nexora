<?php

namespace Modules\PharmaMarketing\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\PharmaMarketing\Models\Customer;
use Modules\PharmaMarketing\Models\CustomerContact;
use Modules\Platform\Models\User;
use Modules\Platform\Models\PlatformTier;
use Modules\Platform\Models\UserTierAssignment;
use Modules\Platform\Contracts\Repositories\ActorRepositoryInterface;
use Modules\Platform\Contracts\Repositories\UserRepositoryInterface;
use Modules\Platform\Contracts\Services\OrgScopeResolverInterface;

class CustomerService
{
    public function __construct(
        protected UserRepositoryInterface   $users,
        protected ActorRepositoryInterface  $actors,
        protected OrgScopeResolverInterface $scope,
    ) {}

// ─────────────────────────────────────────────────────────
// Create Customer
// ─────────────────────────────────────────────────────────

public function create(array $data, string $orgId): Customer
{
    $customer = DB::connection('pharma_marketing')->transaction(function () use ($data, $orgId) {

        // Always store customers under ROOT org
        $rootOrgId = $this->scope->rootId($orgId);
        $isBranch  = $rootOrgId !== $orgId;

        $customer = Customer::create([
            'org_id'                => $rootOrgId,
            'home_branch_id'        => $isBranch ? $orgId : null,
            'assigned_officer_id'   => $data['assigned_officer_id'] ?? null,
            'registration_source'   => $data['registration_source'] ?? 'admin',
            'customer_type'         => $data['customer_type'] ?? 'b2b',
            'name'                  => $data['name'],
            'code'                  => $data['code'] ?? $this->generateCode($rootOrgId),
            'category'              => $data['category'] ?? null,
            'tier'                  => $data['tier'] ?? 'standard',
            'status'                => $data['status'] ?? 'active',
            'business_registration' => $data['business_registration'] ?? null,
            'tax_pin'               => $data['tax_pin'] ?? null,
            'address'               => $data['address'] ?? null,
            'street'                => $data['street'] ?? null,
            'ward'                  => $data['ward'] ?? null,
            'city'                  => $data['city'] ?? null,
            'county'                => $data['county'] ?? null,
            'country'               => $data['country'] ?? 'TANZANIA',
            'latitude'              => $data['latitude'] ?? null,
            'longitude'             => $data['longitude'] ?? null,
            'gps_accuracy_meters'   => $data['gps_accuracy_meters'] ?? null,
            'phone'                 => $data['phone'] ?? null,
            'alt_phone'             => $data['alt_phone'] ?? null,
            'email'                 => $data['email'] ?? null,
            'whatsapp_number'       => $data['whatsapp_number'] ?? null,
            'receives_whatsapp'     => $data['receives_whatsapp'] ?? true,
            'receives_sms'          => $data['receives_sms'] ?? true,
            'receives_in_app'       => $data['receives_in_app'] ?? true,
            'credit_limit'          => $data['credit_limit'] ?? 0,
            'currency'              => $data['currency'] ?? 'TZS',
            'notes'                 => $data['notes'] ?? null,
            'metadata'              => $data['metadata'] ?? null,
        ]);

        // ─────────────────────────────────────────────
        // Contacts
        // ─────────────────────────────────────────────

        if (!empty($data['contacts'])) {
            foreach ($data['contacts'] as $index => $contact) {
                CustomerContact::create([
                    'customer_id'     => $customer->id,
                    'name'            => $contact['name'],
                    'role'            => $contact['role'] ?? null,
                    'phone'           => $contact['phone'] ?? null,
                    'email'           => $contact['email'] ?? null,
                    'whatsapp_number' => $contact['whatsapp_number'] ?? null,
                    'is_primary'      => $contact['is_primary'] ?? ($index === 0),
                    'notes'           => $contact['notes'] ?? null,
                ]);
            }
        } elseif (!empty($data['contact_name'])) {
            CustomerContact::create([
                'customer_id' => $customer->id,
                'name'        => $data['contact_name'],
                'role'        => $data['contact_role'] ?? null,
                'phone'       => $data['contact_phone'] ?? null,
                'is_primary'  => true,
            ]);
        }

        return $customer->load('contacts');
    });
    // ─────────────────────────────────────────────
    // Create app login AFTER pharma transaction commits
    // ─────────────────────────────────────────────

    if (!empty($data['app_password']) && !empty($data['email'])) {
        $this->createOrUpdateAppLogin(
            customer:  $customer,
            email:     $data['email'],
            password:  $data['app_password'],
            officerId: $data['assigned_officer_id'] ?? null,
        );
    }

    return $customer;
}

    // ─────────────────────────────────────────────────────────
    // List Customers
    // ─────────────────────────────────────────────────────────

    public function list(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        $rootOrgId = $this->scope->rootId($orgId);

        return Customer::where('org_id', $rootOrgId)
            ->when(isset($filters['customer_type']), fn($q) => $q->where('customer_type', $filters['customer_type']))
            ->when(isset($filters['status']),        fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['category']),      fn($q) => $q->where('category', $filters['category']))
            ->when(isset($filters['tier']),          fn($q) => $q->where('tier', $filters['tier']))
            ->when(isset($filters['officer_id']),    fn($q) => $q->where('assigned_officer_id', $filters['officer_id']))
            ->when(isset($filters['branch_id']),     fn($q) => $q->where('home_branch_id', $filters['branch_id']))
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where(function ($qq) use ($search) {
                    $qq->where('name',  'ilike', "%{$search}%")
                       ->orWhere('phone', 'ilike', "%{$search}%")
                       ->orWhere('code',  'ilike', "%{$search}%")
                       ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->with(['contacts' => fn($q) => $q->where('is_primary', true)])
            ->orderBy('name')
            ->paginate($perPage);
    }

    // ─────────────────────────────────────────────────────────
    // Get Single Customer
    // ─────────────────────────────────────────────────────────

    public function get(string $id): Customer
    {
        return Customer::with([
            'contacts',
            'visits' => fn($q) => $q->limit(5),
        ])->findOrFail($id);
    }

    // ─────────────────────────────────────────────────────────
    // Update Customer
    // ─────────────────────────────────────────────────────────

    public function update(string $id, array $data): Customer
    {
        $customer = Customer::findOrFail($id);

        $updated = DB::connection('pharma_marketing')->transaction(function () use ($customer, $data) {

            $customer->update([
                'assigned_officer_id'   => $data['assigned_officer_id'] ?? $customer->assigned_officer_id,
                'customer_type'         => $data['customer_type'] ?? $customer->customer_type,
                'name'                  => $data['name'] ?? $customer->name,
                'code'                  => $data['code'] ?? $customer->code,
                'category'              => array_key_exists('category', $data) ? $data['category'] : $customer->category,
                'tier'                  => $data['tier'] ?? $customer->tier,
                'status'                => $data['status'] ?? $customer->status,
                'business_registration' => array_key_exists('business_registration', $data) ? $data['business_registration'] : $customer->business_registration,
                'tax_pin'               => array_key_exists('tax_pin', $data) ? $data['tax_pin'] : $customer->tax_pin,
                'address'               => array_key_exists('address', $data) ? $data['address'] : $customer->address,
                'street'                => array_key_exists('street', $data) ? $data['street'] : $customer->street,
                'ward'                  => array_key_exists('ward', $data) ? $data['ward'] : $customer->ward,
                'city'                  => array_key_exists('city', $data) ? $data['city'] : $customer->city,
                'county'                => array_key_exists('county', $data) ? $data['county'] : $customer->county,
                'country'               => $data['country'] ?? $customer->country,
                'latitude'              => array_key_exists('latitude', $data) ? $data['latitude'] : $customer->latitude,
                'longitude'             => array_key_exists('longitude', $data) ? $data['longitude'] : $customer->longitude,
                'gps_accuracy_meters'   => array_key_exists('gps_accuracy_meters', $data) ? $data['gps_accuracy_meters'] : $customer->gps_accuracy_meters,
                'phone'                 => array_key_exists('phone', $data) ? $data['phone'] : $customer->phone,
                'alt_phone'             => array_key_exists('alt_phone', $data) ? $data['alt_phone'] : $customer->alt_phone,
                'email'                 => array_key_exists('email', $data) ? $data['email'] : $customer->email,
                'whatsapp_number'       => array_key_exists('whatsapp_number', $data) ? $data['whatsapp_number'] : $customer->whatsapp_number,
                'receives_whatsapp'     => $data['receives_whatsapp'] ?? $customer->receives_whatsapp,
                'receives_sms'          => $data['receives_sms'] ?? $customer->receives_sms,
                'receives_in_app'       => $data['receives_in_app'] ?? $customer->receives_in_app,
                'credit_limit'          => $data['credit_limit'] ?? $customer->credit_limit,
                'currency'              => $data['currency'] ?? $customer->currency,
                'notes'                 => array_key_exists('notes', $data) ? $data['notes'] : $customer->notes,
                'metadata'              => array_key_exists('metadata', $data) ? $data['metadata'] : $customer->metadata,
            ]);

            // ─────────────────────────────────────────────
            // Update Primary Contact
            // ─────────────────────────────────────────────

            if (
                array_key_exists('contact_name', $data) ||
                array_key_exists('contact_phone', $data) ||
                array_key_exists('contact_role', $data)
            ) {
                $primary = CustomerContact::where('customer_id', $customer->id)
                    ->where('is_primary', true)
                    ->first();

                $contactData = [
                    'name'  => $data['contact_name'] ?? '',
                    'role'  => $data['contact_role'] ?? null,
                    'phone' => $data['contact_phone'] ?? null,
                ];

                if ($primary) {
                    $primary->update($contactData);
                } else {
                    CustomerContact::create(array_merge($contactData, ['customer_id' => $customer->id, 'is_primary' => true]));
                }
            }

            return $customer->fresh('contacts');
        });

        // ── Update app login AFTER pharma transaction commits ─────────────
        if (!empty($data['app_password'])) {
            $this->createOrUpdateAppLogin(
                customer: $customer->fresh(),
                email:    $data['email'] ?? $customer->email,
                password: $data['app_password'],
            );
        }

        return $updated;
    }

    // ─────────────────────────────────────────────────────────
    // Assign Officer
    // ─────────────────────────────────────────────────────────

    public function assignOfficer(string $customerId, string $officerActorId): Customer
    {
        $customer = Customer::findOrFail($customerId);
        $customer->update(['assigned_officer_id' => $officerActorId]);
        return $customer->fresh();
    }

    // ─────────────────────────────────────────────────────────
    // Add Contact
    // ─────────────────────────────────────────────────────────

    public function addContact(string $customerId, array $data): CustomerContact
    {
        if ($data['is_primary'] ?? false) {
            CustomerContact::where('customer_id', $customerId)->update(['is_primary' => false]);
        }
        return CustomerContact::create(array_merge($data, ['customer_id' => $customerId]));
    }

    // ─────────────────────────────────────────────────────────
    // Create From Registration
    // ─────────────────────────────────────────────────────────

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
            $rootOrgId = $this->scope->rootId($orgId);

            $existing = Customer::where('platform_user_id', $platformUserId)
                ->where('org_id', $rootOrgId)
                ->first();

            if ($existing) {
                return $existing;
            }

            $platformUser = User::find($platformUserId);

            return Customer::create([
                'org_id'              => $rootOrgId,
                'platform_user_id'    => $platformUserId,
                'actor_id'            => $platformUser?->actor_id,
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

    // ─────────────────────────────────────────────────────────
    // Link Existing Customer To Platform User
    // ─────────────────────────────────────────────────────────

    public function linkPlatformUser(string $orgId, string $platformUserId, string $email): ?Customer
    {
        $rootOrgId = $this->scope->rootId($orgId);

        $customer = Customer::where('org_id', $rootOrgId)
            ->where('email', $email)
            ->whereNull('platform_user_id')
            ->first();

        if ($customer) {
            $platformUser = User::find($platformUserId);
            $customer->update([
                'platform_user_id'    => $platformUserId,
                'actor_id'            => $platformUser?->actor_id,
                'registration_source' => 'self_registered',
            ]);
            return $customer->fresh();
        }

        return null;
    }

    public function linkPlatformUserByUsername(
        string $orgId,
        string $platformUserId,
        string $username,
    ): ?Customer {
        $rootOrgId = $this->scope->rootId($orgId);

        $parts  = explode('_', $username);
        $suffix = end($parts);

        if (strlen($suffix) < 4) {
            return null;
        }

        $customer = Customer::where('org_id', $rootOrgId)
            ->whereNull('platform_user_id')
            ->whereRaw('RIGHT(id::text, 4) = ?', [strtoupper(substr($suffix, -4))])
            ->first();

        if ($customer) {
            $platformUser = User::find($platformUserId);
            $customer->update([
                'platform_user_id'    => $platformUserId,
                'actor_id'            => $platformUser?->actor_id,
                'registration_source' => 'self_registered',
            ]);
            return $customer->fresh();
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────
    // Generate Customer Code
    // ─────────────────────────────────────────────────────────

    protected function generateCode(string $orgId): string
    {
        $rootOrgId = $this->scope->rootId($orgId);

        $last = Customer::where('org_id', $rootOrgId)
            ->whereNotNull('code')
            ->orderBy('created_at', 'desc')
            ->value('code');

        $seq = $last
            ? ((int) preg_replace('/[^0-9]/', '', $last)) + 1
            : 1;

        return sprintf('CUST-%05d', $seq);
    }

    // ─────────────────────────────────────────────────────────
    // App Login Helper
    // ─────────────────────────────────────────────────────────

    protected function createOrUpdateAppLogin(
        Customer $customer,
        ?string $email,
        string $password,
        ?string $officerId = null,
    ): void {

        if (empty($email)) {
            \Illuminate\Support\Facades\Log::warning('createOrUpdateAppLogin: email is empty, skipping', [
                'customer_id' => $customer->id,
            ]);
            return;
        }

        \Illuminate\Support\Facades\Log::info('createOrUpdateAppLogin: starting', [
            'customer_id'  => $customer->id,
            'email'        => $email,
            'password_len' => strlen($password),
        ]);

        $existing = User::where('email', $email)->first();

        if ($existing) {
            \Illuminate\Support\Facades\Log::info('createOrUpdateAppLogin: existing user found, updating password', [
                'user_id' => $existing->id,
            ]);

            $existing->forceFill([
                'password' => \Illuminate\Support\Facades\Hash::make($password),
            ])->save();

            if (!$customer->platform_user_id) {
                $customer->update(['platform_user_id' => $existing->id]);
            }

            return;
        }

        $username = strtolower(
            preg_replace('/[^a-zA-Z0-9_]/', '_', explode('@', $email)[0])
        ) . '_' . substr($customer->id, -4);

        \Illuminate\Support\Facades\Log::info('createOrUpdateAppLogin: no existing user, creating new', [
            'email'    => $email,
            'username' => $username,
        ]);

        try {
            DB::connection('platform')->transaction(function () use ($customer, $email, $password, $username) {

                $actor = $this->actors->create([
                    'display_name' => $customer->name,
                    'status'       => 'active',
                ]);

                \Illuminate\Support\Facades\Log::info('createOrUpdateAppLogin: actor created', [
                    'actor_id' => $actor->id,
                ]);

                $user = $this->users->create([
                    'username' => $username,
                    'email'    => $email,
                    'password' => $password,
                    'actor_id' => $actor->id,
                    'status'   => 'active',
                ]);

                \Illuminate\Support\Facades\Log::info('createOrUpdateAppLogin: user created', [
                    'user_id'    => $user->id,
                    'pwd_prefix' => substr($user->password, 0, 10),
                ]);

                $this->actors->assignType($actor->id, 'user');

                $defaultTier = PlatformTier::where('is_default', true)
                    ->where('is_active', true)
                    ->first();

                if ($defaultTier) {
                    UserTierAssignment::create([
                        'user_id'     => $user->id,
                        'tier_id'     => $defaultTier->id,
                        'assigned_by' => null,
                        'status'      => 'active',
                    ]);
                }

                $customer->update([
                    'platform_user_id' => $user->id,
                    'actor_id'         => $actor->id,
                ]);

                \Illuminate\Support\Facades\Log::info('createOrUpdateAppLogin: completed successfully', [
                    'user_id'     => $user->id,
                    'customer_id' => $customer->id,
                ]);
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('createOrUpdateAppLogin: FAILED', [
                'customer_id' => $customer->id,
                'email'       => $email,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
        }
    }
}