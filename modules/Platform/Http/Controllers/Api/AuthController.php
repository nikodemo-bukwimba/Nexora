<?php

namespace Modules\Platform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\AuthServiceInterface;
use Modules\Platform\Http\Requests\Auth\ApiLoginRequest;
use Modules\Platform\Http\Requests\Auth\ApiRegisterRequest;
use Modules\Platform\Models\User;

class AuthController extends Controller
{
    public function __construct(
        protected AuthServiceInterface $auth
    ) {}

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

    /**
     * GET /api/v1/auth/me
     *
     * Returns the authenticated user with their role and permissions
     * scoped to the requested org node.
     *
     * When org_id is supplied: returns permissions for THAT org node.
     * When org_id is absent:   picks the user's highest-level active
     *                          membership across ALL orgs (useful on
     *                          first login when the client doesn't yet
     *                          know the org_id).
     *
     * KEY: The resolved org_id is ALWAYS echoed back in the user object
     * so the client can persist it and use it on subsequent calls.
     * This eliminates the need for a hardcoded org_id in the app.
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

        // Always return the resolved org_id so the client can persist it.
        // This is the critical fix: without this the client can never
        // automatically discover which org_id to scope /auth/me to.
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
                'org_id'       => $membership?->org_id,
                // 'org_id'       => $resolvedOrgId,   // ← KEY: echoed back
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