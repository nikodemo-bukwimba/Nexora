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
            'name'          => ['required', 'string', 'min:2'],
            'customer_type' => ['required', 'string', 'in:b2b,b2c'],
            'app_password'  => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = $this->customers->create($orgId, $request->except(['app_password', 'app_password_confirmation']));

        // If admin set a password, auto-register the customer on the platform
        if ($request->filled('app_password') && $request->filled('email')) {
            try {
                $authService = app(\Modules\Platform\Contracts\Services\AuthServiceInterface::class);
                $username = \Illuminate\Support\Str::slug($request->email) . '_' . substr(uniqid(), -4);

                $user = $authService->register([
                    'name'     => $request->name,
                    'username' => $username,
                    'email'    => $request->email,
                    'password' => $request->app_password,
                ]);

                // Link to customer record
                $customer->update([
                    'platform_user_id'    => $user->id,
                    'registration_source' => 'admin',
                ]);
            } catch (\Throwable $e) {
                \Log::warning("Failed to create platform account for customer {$customer->id}: {$e->getMessage()}");
            }
        }

        return response()->json(['message' => 'Customer created.', 'customer' => $customer->fresh(['contacts'])], 201);
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
