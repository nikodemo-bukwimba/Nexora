<?php

namespace Modules\PharmaMarketing\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\PharmaMarketing\Models\Customer;
use Modules\PharmaMarketing\Models\CustomerContact;

class CustomerService
{
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

    public function list(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        return Customer::where('org_id', $orgId)
            ->when(isset($filters['customer_type']),      fn($q) => $q->where('customer_type', $filters['customer_type']))
            ->when(isset($filters['status']),              fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['category']),            fn($q) => $q->where('category', $filters['category']))
            ->when(isset($filters['tier']),                fn($q) => $q->where('tier', $filters['tier']))
            ->when(isset($filters['officer_id']),          fn($q) => $q->where('assigned_officer_id', $filters['officer_id']))
            ->when(isset($filters['search']),              fn($q) => $q->where(function ($q) use ($filters) {
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

    private function generateCode(string $orgId): string
    {
        $last = Customer::where('org_id', $orgId)
            ->whereNotNull('code')
            ->orderBy('created_at', 'desc')
            ->value('code');

        $seq = $last ? ((int) preg_replace('/[^0-9]/', '', $last)) + 1 : 1;
        return sprintf('CUST-%05d', $seq);
    }
}
