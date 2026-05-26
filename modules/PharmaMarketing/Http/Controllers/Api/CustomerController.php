<?php
// FILE: modules/PharmaMarketing/Http/Controllers/Api/CustomerController.php
// CHANGES:
//   1. store() — customer_type already validated ✅, no change needed
//   2. update() — was using $request->all() with no validation, passing
//      unknown fields. Now validates explicitly and passes contact fields
//      (contact_name, contact_phone, contact_role) through to CustomerService.
//   Everything else unchanged.

namespace Modules\PharmaMarketing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
            $request->except(['app_password', 'app_password_confirmation']),
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

    /**
     * PATCH /api/v1/pharma/customers/{id}
     *
     * CHANGE: Added explicit validation + contact fields.
     * Previously used $request->all() with no validation — unknown columns
     * were silently dropped by Eloquent but contact fields were also dropped.
     * Now contact_name, contact_phone, contact_role are explicitly accepted
     * and passed to CustomerService::update() which upserts the primary contact.
     */
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

        $customer = $this->customers->update($id, $request->all()); // ← was $request->validated()

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
}
