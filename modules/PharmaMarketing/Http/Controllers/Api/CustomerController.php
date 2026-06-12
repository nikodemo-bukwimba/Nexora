<?php
// FILE: modules/PharmaMarketing/Http/Controllers/Api/CustomerController.php

namespace Modules\PharmaMarketing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\PharmaMarketing\Models\Customer;
use Modules\PharmaMarketing\Services\CustomerService;

class CustomerController extends Controller
{
    public function __construct(protected CustomerService $customers) {}

    /** GET /api/v1/pharma/orgs/{orgId}/customers */
    public function index(Request $request, string $orgId): JsonResponse
    {
        return response()->json(
            $this->customers->list(
                $orgId,
                $request->only(['customer_type', 'status', 'category', 'tier', 'officer_id', 'search', 'branch_id']),
                (int) $request->get('per_page', 25)
            )
        );
    }

    /** POST /api/v1/pharma/orgs/{orgId}/customers */
    public function store(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'min:2'],
            'customer_type' => ['required', 'string', 'in:b2b,b2c'],
            'app_password'  => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = $this->customers->create(
            $request->all(),
            $orgId
        );

        return response()->json([
            'message'  => 'Customer created.',
            'customer' => $customer->fresh(['contacts']),
        ], 201);
    }

    /** GET /api/v1/pharma/customers/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json($this->customers->get($id));
    }

    /** PATCH /api/v1/pharma/customers/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('Customer update request', [
            'id'   => $id,
            'body' => $request->all(),
        ]);

        $request->validate([
            'name'          => ['sometimes', 'string', 'min:2'],
            'customer_type' => ['sometimes', 'string', 'in:b2b,b2c'],
            'category'      => ['sometimes', 'nullable', 'string'],
            'tier'          => ['sometimes', 'nullable', 'string'],
            'status'        => ['sometimes', 'string', 'in:active,suspended,inactive'],
            'phone'         => ['sometimes', 'nullable', 'string'],
            'alt_phone'     => ['sometimes', 'nullable', 'string'],
            'email'         => ['sometimes', 'nullable', 'email'],
            'address'       => ['sometimes', 'nullable', 'string'],
            'city'          => ['sometimes', 'nullable', 'string'],
            'county'        => ['sometimes', 'nullable', 'string'],
            'country'       => ['sometimes', 'nullable', 'string'],
            'notes'         => ['sometimes', 'nullable', 'string'],
            'credit_limit'  => ['sometimes', 'nullable', 'numeric'],
            'contact_name'  => ['sometimes', 'nullable', 'string'],
            'contact_phone' => ['sometimes', 'nullable', 'string'],
            'contact_role'  => ['sometimes', 'nullable', 'string'],
        ]);

        $customer = $this->customers->update($id, $request->all());

        return response()->json([
            'message'  => 'Customer updated.',
            'customer' => $customer,
        ]);
    }

    /** POST /api/v1/pharma/customers/{id}/assign */
    public function assign(Request $request, string $id): JsonResponse
    {
        $request->validate(['officer_actor_id' => ['required', 'string', 'size:26']]);
        $customer = $this->customers->assignOfficer($id, $request->officer_actor_id);
        return response()->json(['message' => 'Officer assigned.', 'customer' => $customer]);
    }

    /** POST /api/v1/pharma/customers/{id}/contacts */
    public function addContact(Request $request, string $id): JsonResponse
    {
        $request->validate(['name' => ['required', 'string']]);
        $contact = $this->customers->addContact($id, $request->all());
        return response()->json(['message' => 'Contact added.', 'contact' => $contact], 201);
    }

    /**
     * GET /api/v1/pharma/customers/me/contacts
     *
     * Returns the people this customer can start a help-request conversation
     * with, ordered by:
     *   1. Assigned officer (is_primary = true)
     *   2. Other officers
     *   3. Managers  (any role whose slug contains 'manager')
     *   4. Admins / owners
     *
     * FIX: The previous version used whereIn() with exact slug values, which
     * missed slug variants like 'branch_manager', 'head_manager', 'org_admin'.
     * Now uses LIKE patterns on slug (same logic as the org role seed slugs).
     * Also fixes the hardcoded 'admin' role label — managers now return their
     * actual role slug so the Flutter UI can colour/label them correctly.
     */
    public function myContacts(Request $request): JsonResponse
    {
        $actorId  = $request->user()->actor_id;
        $contacts = collect();

        // ── Resolve customer record ───────────────────────────────────────
        $customer = Customer::where('actor_id', $actorId)->first();

        // ── 1. Assigned officer (is_primary) ──────────────────────────────
        if ($customer?->assigned_officer_id) {
            $officer = DB::connection('platform')
                ->table('actors')
                ->where('id', $customer->assigned_officer_id)
                ->where('status', 'active')
                ->first();

            if ($officer) {
                $contacts->push([
                    'actor_id'   => $officer->id,
                    'name'       => $officer->display_name,
                    'role'       => 'officer',
                    'is_primary' => true,
                ]);
            }
        }

        // ── 2. Other org members with contactable roles ───────────────────
        //
        // Queries both the customer's branch (home_branch_id) AND the root org
        // so head-office managers/admins are also discoverable.
        //
        // Role matching uses LIKE patterns on the role slug, which is
        // auto-generated from the role name (lowercase + underscores).
        // This covers: officer, field_officer, manager, branch_manager,
        // head_manager, admin, org_admin, owner, etc.
        if ($customer?->org_id) {
            $orgIds = array_filter(array_unique([
                $customer->org_id,
                $customer->home_branch_id ?? null,
            ]));

            $members = DB::connection('platform')
                ->table('org_memberships as om')
                ->join('org_roles as r',  'r.id',  '=', 'om.org_role_id')
                ->join('users as u',      'u.id',  '=', 'om.user_id')
                ->join('actors as a',     'a.id',  '=', 'u.actor_id')
                ->whereIn('om.org_id', $orgIds)
                ->where('om.status', 'active')
                ->where('u.status',  'active')
                // Exclude the customer themselves
                ->where('a.id', '!=', $actorId)
                // Exclude already-added contacts (assigned officer)
                ->whereNotIn('a.id', $contacts->pluck('actor_id')->filter()->values()->toArray())
                // Match contactable roles by slug pattern
                ->where(function ($q) {
                    $q->where('r.slug', 'like', '%officer%')
                      ->orWhere('r.slug', 'like', '%manager%')
                      ->orWhere('r.slug', 'like', '%admin%')
                      ->orWhere('r.slug', 'like', '%owner%');
                })
                ->select([
                    'a.id          as actor_id',
                    'a.display_name as name',
                    'r.slug        as role',   // ← use actual slug, not hardcoded 'admin'
                ])
                ->distinct()
                ->get();

            foreach ($members as $member) {
                $contacts->push([
                    'actor_id'   => $member->actor_id,
                    'name'       => $member->name,
                    'role'       => $member->role,   // slug: 'manager', 'branch_manager', 'admin', etc.
                    'is_primary' => false,
                ]);
            }
        }

        // ── 3. Sort: assigned officer → officer → manager → admin/owner ───
        $sorted = $contacts->sortBy(static function (array $c): int {
            if ($c['is_primary'])                             return 0;
            $role = strtolower($c['role'] ?? '');
            if (str_contains($role, 'officer'))               return 1;
            if (str_contains($role, 'manager'))               return 2;
            return 3;
        })->values();

        return response()->json(['data' => $sorted]);
    }
}