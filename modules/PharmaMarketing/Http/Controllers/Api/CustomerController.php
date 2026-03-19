<?php

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
        return response()->json($this->customers->list($orgId, $request->only(['customer_type', 'status', 'category', 'tier', 'officer_id', 'search']), (int) $request->get('per_page', 25)));
    }

    /** POST /api/v1/pharma/orgs/{orgId}/customers */
    public function store(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'customer_type' => ['required', 'string', 'in:b2b,b2c'],
        ]);
        $customer = $this->customers->create($orgId, $request->all());
        return response()->json(['message' => 'Customer created.', 'customer' => $customer], 201);
    }

    /** GET /api/v1/pharma/customers/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json($this->customers->get($id));
    }

    /** PATCH /api/v1/pharma/customers/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $customer = $this->customers->update($id, $request->all());
        return response()->json(['message' => 'Customer updated.', 'customer' => $customer]);
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
