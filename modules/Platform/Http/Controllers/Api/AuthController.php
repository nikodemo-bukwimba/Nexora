<?php
// FILE: modules/Platform/Http/Controllers/Api/AuthController.php
// CHANGE: me() now resolves pm_officers.branch_id and returns
//         branch_id + branch_name in the user payload.
//         Everything else (register, login, logout) is unchanged.

namespace Modules\Platform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\AuthServiceInterface;
use Modules\Platform\Http\Requests\Auth\ApiLoginRequest;
use Modules\Platform\Http\Requests\Auth\ApiRegisterRequest;
use Modules\Platform\Models\User;
use Modules\Platform\Models\Organization;
// ── NEW ──
use Modules\PharmaMarketing\Models\PmOfficer;
// ─────────

class AuthController extends Controller
{
    public function __construct(
        protected AuthServiceInterface $auth
    ) {}

    // ── Unchanged ─────────────────────────────────────────────────────

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

    // ── CHANGED: me() ────────────────────────────────────────────────

    /**
     * GET /api/v1/auth/me
     *
     * Returns the authenticated user scoped to their org membership.
     *
     * NEW: Also resolves the officer's CURRENT branch from pm_officers
     * and returns branch_id + branch_name so Flutter always has the
     * up-to-date branch after a transfer.
     */
    public function me(Request $request): JsonResponse
    {
        $user  = $request->user()->load('actor');
        $orgId = $request->query('org_id');

        // ── Resolve platform membership (unchanged logic) ─────────────
        $membership = $user->orgMemberships()
            ->with(['orgRole.permissions', 'organization'])
            ->where('status', 'active')
            ->when($orgId, fn($q) => $q->where('org_id', $orgId))
            ->orderByDesc('level')
            ->first();

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

        // ── NEW: Resolve current branch from pm_officers ──────────────
        //
        // We look up pm_officers using the ROOT org (not the branch),
        // because pm_officers.org_id is always the root org.
        // pm_officers.branch_id is the officer's CURRENT branch —
        // it is updated atomically by OfficerService::transferOfficer().
        // This is the single source of truth for branch assignment.
        //
        $branchId   = null;
        $branchName = null;

        if ($rootOrgId) {
            $officer = PmOfficer::where('platform_user_id', $user->id)
                ->where('org_id', $rootOrgId)
                ->first();

            if ($officer && $officer->branch_id) {
                $branchId = $officer->branch_id;

                // Resolve the branch name from the organizations table
                $branch     = Organization::find($branchId);
                $branchName = $branch?->name;
            }
        }
        // ─────────────────────────────────────────────────────────────

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
                // ── NEW ──────────────────────────────────────────────
                'branch_id'   => $branchId,   // always current, from pm_officers
                'branch_name' => $branchName,
                // ─────────────────────────────────────────────────────
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
