<?php
// FILE: modules/Platform/Http/Controllers/Api/AuthController.php
// PATH: modules/Platform/Http/Controllers/Api/AuthController.php
//
// CHANGE: me() — after resolving org membership, resolve pm_customers record.
//
// HOW THE CUSTOMER LINKING WORKS:
//
//   Case A — Admin pre-created the customer with an email, customer self-registers:
//     linkPlatformUser() matches by email → links platform_user_id → returns customer
//
//   Case B — Customer self-registered first (no pre-existing record):
//     createFromRegistration() creates a new pm_customers record for them
//
//   Case C — Officer/admin user (no customer record expected):
//     pm_customers lookup returns null → customer_id omitted from response
//
// The customer app reads customer_id from /auth/me and uses it to scope API calls.
// Without this, every self-registered customer is disconnected from admin-created records.

namespace Modules\Platform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\AuthServiceInterface;
use Modules\Platform\Http\Requests\Auth\ApiLoginRequest;
use Modules\Platform\Http\Requests\Auth\ApiRegisterRequest;
use Modules\Platform\Models\User;
use Modules\Platform\Models\Organization;
// ── Existing import ───────────────────────────────────────────────────────────
use Modules\PharmaMarketing\Models\PmOfficer;
// ── NEW ───────────────────────────────────────────────────────────────────────
use Modules\PharmaMarketing\Models\Customer;
// ─────────────────────────────────────────────────────────────────────────────

class AuthController extends Controller
{
    public function __construct(
        protected AuthServiceInterface $auth,
    ) {}

    // ── Unchanged ─────────────────────────────────────────────────────────────

    public function register(ApiRegisterRequest $request): JsonResponse
    {
        $user  = $this->auth->register($request->validated());
        $token = $user->createToken($request->device_name ?? 'api')->plainTextToken;
        $this->auth->recordLogin($user, $request->ip());

        return response()->json([
            'user'  => [
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'status'   => $user->status,
            ],
            'token' => $token,
        ], 201);
    }

    public function login(ApiLoginRequest $request): JsonResponse
    {
        $token = $this->auth->loginWithToken(
            $request->email,
            $request->password,
            $request->device_name ?? 'api'
        );
        if (! $token) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }
        $user = User::where('email', $request->email)->first();
        $this->auth->recordLogin($user, $request->ip());
        return response()->json(['token' => $token]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->revokeToken($request->user());
        return response()->json(['message' => 'Logged out.']);
    }

    // ── CHANGED: me() ─────────────────────────────────────────────────────────

    public function me(Request $request): JsonResponse
    {
        $user  = $request->user()->load('actor');
        $orgId = $request->query('org_id');

        // ── Resolve platform membership ────────────────────────────────────
        if ($orgId) {
            $membership = $user->orgMemberships()
                ->with(['orgRole.permissions', 'organization'])
                ->where('status', 'active')
                ->where('org_id', $orgId)
                ->first();
        } else {
            $allMemberships = $user->orgMemberships()
                ->with(['orgRole.permissions', 'organization'])
                ->where('status', 'active')
                ->get();

            // Prefer branch membership over root
            $membership = $allMemberships->first(
                fn($m) => $m->organization?->type !== 'root'
            ) ?? $allMemberships->first();
        }

        $orgRole     = $membership?->orgRole;
        $org         = $membership?->organization;
        $permissions = $orgRole?->permissions ?? collect();

        $rolePayload = null;
        if ($orgRole) {
            $rolePayload = [
                'id'   => $orgRole->id,
                'name' => $orgRole->name,
                'slug' => $orgRole->slug ?? $this->slugify($orgRole->name),
            ];
        }

        $resolvedOrgId = $membership?->org_id;
        $rootOrgId     = $org?->root_org_id ?? $resolvedOrgId;

        // ── Resolve officer branch from pm_officers ────────────────────────
        $branchId   = null;
        $branchName = null;

        if ($rootOrgId) {
            $officer = PmOfficer::where('platform_user_id', $user->id)
                ->where('org_id', $rootOrgId)
                ->first();

            if ($officer && $officer->branch_id) {
                $branchId   = $officer->branch_id;
                $branch     = Organization::find($branchId);
                $branchName = $branch?->name;
            }
        }

        // ── NEW: Resolve customer record from pm_customers ─────────────────
        //
        // FIX: No cross-connection subqueries. Customer model uses pharma_marketing
        // connection; organizations is on platform. Resolve org tree IDs in PHP first.
        //
        // Resolution order:
        //   1. Look up by platform_user_id (already linked — fastest path)
        //   2. Not found → try linkPlatformUser() by email (admin pre-created record)
        //   3. Still not found + customer role → createFromRegistration()
        //   4. No org or admin/officer role → skip
        $customerId    = null;
        $customerOrgId = null;

        if ($rootOrgId) {
            // Resolve all org IDs in this tree using the platform connection (PHP-side)
            $treeOrgIds = \Illuminate\Support\Facades\DB::connection('platform')
                ->table('organizations')
                ->where('root_org_id', $rootOrgId)
                ->orWhere('id', $rootOrgId)
                ->pluck('id')
                ->toArray();

            // Step 1: already linked — search across the whole tree using PHP array
            $customer = Customer::where('platform_user_id', $user->id)
                ->whereIn('org_id', $treeOrgIds)
                ->first();

            // Step 2: admin pre-created a customer with this email — link them
            if (! $customer) {
                $customerService = app(\Modules\PharmaMarketing\Services\CustomerService::class);
                $customer = $customerService->linkPlatformUser(
                    orgId:          $rootOrgId,
                    platformUserId: $user->id,
                    email:          $user->email,
                );
            }

            // Step 3: new self-registrant with no pre-existing record
            if (! $customer) {
                $roleSlug       = $rolePayload['slug'] ?? '';
                $isCustomerRole = in_array($roleSlug, ['customer', 'user', 'viewer', '']);

                if ($isCustomerRole) {
                    try {
                        if (! isset($customerService)) {
                            $customerService = app(\Modules\PharmaMarketing\Services\CustomerService::class);
                        }
                        $customer = $customerService->createFromRegistration(
                            orgId:          $rootOrgId,
                            platformUserId: $user->id,
                            displayName:    $user->actor?->display_name ?? $user->username,
                            email:          $user->email,
                            phone:          null,
                        );
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning(
                            "AuthController: createFromRegistration failed for user {$user->id}: " . $e->getMessage()
                        );
                    }
                }
            }

            if ($customer) {
                $customerId    = $customer->id;
                $customerOrgId = $customer->org_id;
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        return response()->json([
            'user' => [
                'id'          => $user->id,
                'actor_id'    => $user->actor_id,
                'name'        => $user->actor?->display_name ?? $user->username,
                'username'    => $user->username,
                'email'       => $user->email,
                'status'      => $user->status,
                'is_active'   => $user->status === 'active',
                'org_id'      => $resolvedOrgId,
                'root_org_id' => $rootOrgId,
                'org_status'  => $org?->status,
                'org_name'    => $org?->name,
                'branch_id'   => $branchId,
                'branch_name' => $branchName,
                // ── NEW: customer context ─────────────────────────────────
                'customer_id'     => $customerId,    // pm_customers.id
                'customer_org_id' => $customerOrgId, // branch the customer belongs to
                // ─────────────────────────────────────────────────────────
                'primary_role' => $rolePayload,
                'roles'        => $rolePayload ? [$rolePayload] : [],
            ],
            'permissions' => $permissions->map(fn($p) => [
                'id'   => $p->id,
                'name' => $p->name,
                'slug' => $p->name,
            ])->values(),
        ]);
    }

    private function slugify(string $name): string
    {
        return strtolower(preg_replace('/\s+/', '_', trim($name)));
    }
}
