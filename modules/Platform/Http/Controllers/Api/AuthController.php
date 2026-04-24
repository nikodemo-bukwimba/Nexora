<?php

namespace Modules\Platform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\AuthServiceInterface;
use Modules\Platform\Http\Requests\Auth\ApiLoginRequest;
use Modules\Platform\Http\Requests\Auth\ApiRegisterRequest;
use Modules\Platform\Models\User;
use Modules\PharmaMarketing\Services\CustomerService;
use Modules\PharmaMarketing\Services\OfficerService;

class AuthController extends Controller
{
    public function __construct(
        protected AuthServiceInterface $auth,
        protected CustomerService $customerService,
        protected OfficerService $officerService,
    ) {}

    public function register(ApiRegisterRequest $request): JsonResponse
    {
        $user  = $this->auth->register($request->validated());
        $token = $user->createToken($request->device_name ?? 'api')->plainTextToken;
        $this->auth->recordLogin($user, $request->ip());

        $orgId       = $request->input('org_id');
        $appType     = $request->input('app_type'); // 'customer' | 'officer'
        $displayName = $user->actor?->display_name ?? $user->username;

        if ($orgId) {
            try {
                if ($appType === 'officer') {
                    // Try to link to pre-existing pm_officer record (admin-created)
                    $linked = $this->officerService->linkPlatformUser(
                        $orgId,
                        $user->id,
                        $user->actor_id ?? '',
                        $user->email
                    );
                    // If none found, create a self-registered officer record
                    if (! $linked) {
                        $this->officerService->createFromAdminOrg(
                            orgId:          $orgId,
                            branchId:       $orgId, // default to root; branch resolved later
                            platformUserId: $user->id,
                            actorId:        $user->actor_id ?? '',
                            name:           $displayName,
                            email:          $user->email,
                            source:         'self_registered',
                        );
                    }
                } else {
                    // Default: customer app registration
                    $linked = $this->customerService->linkPlatformUser(
                        $orgId,
                        $user->id,
                        $user->email
                    );
                    if (! $linked) {
                        $this->customerService->createFromRegistration(
                            orgId:          $orgId,
                            platformUserId: $user->id,
                            displayName:    $displayName,
                            email:          $user->email,
                        );
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning("Auto-create pharma record failed for user {$user->id}: {$e->getMessage()}");
            }
        }

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

    /**
     * GET /api/v1/auth/me
     *
     * KEY FIX: The response now always includes user.org_id —
     * the org_id of the membership used to scope permissions.
     *
     * Without org_id:  server picks the user's highest-level active
     *                  membership across ALL orgs. Best for first login.
     *
     * With org_id:     server scopes permissions to that specific org
     *                  node. Used on refresh after client stores org_id.
     *
     * The client MUST persist user.org_id from the first response
     * and send it as ?org_id= on all subsequent /auth/me calls.
     * This is what makes branch-member permissions work correctly.
     */
    public function me(Request $request): JsonResponse
    {
        $user  = $request->user()->load('actor');
        $orgId = $request->query('org_id');

        $membership = $user->orgMemberships()
            ->with(['orgRole.permissions'])
            ->where('status', 'active')
            ->when($orgId, fn($q) => $q->where('org_id', $orgId))
            ->orderByDesc('level')
            ->first();

        $orgRole     = $membership?->orgRole;
        $permissions = $orgRole?->permissions ?? collect();

        $rolePayload = null;
        if ($orgRole) {
            $rolePayload = [
                'id'   => $orgRole->id,
                'name' => $orgRole->name,
                'slug' => $orgRole->slug ?? $this->slugify($orgRole->name),
            ];
        }

        // Echo back the org_id that was resolved (from the membership).
        // The Flutter client stores this and reuses it on every refresh.
        // This is the fix that eliminates the hardcoded org_id on the client.
        $resolvedOrgId = $membership?->org_id;

        return response()->json([
            'user' => [
                'id'           => $user->id,
                'actor_id'     => $user->actor_id,
                'name'         => $user->actor?->display_name ?? $user->username,
                'username'     => $user->username,
                'email'        => $user->email,
                'status'       => $user->status,
                'is_active'    => $user->status === 'active',
                // KEY: echoed org_id — client persists this in OrgContext
                'org_id'       => $resolvedOrgId,
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