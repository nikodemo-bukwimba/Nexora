<?php

namespace Modules\Platform\Http\Controllers\Api\Org;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Modules\Platform\Contracts\Services\AuthServiceInterface;
use Modules\Platform\Models\OrgMembership;
use Modules\Platform\Models\OrgRole;
use Modules\Platform\Models\Organization;
use Modules\Platform\Models\User;

class OfficerController extends Controller
{
    public function __construct(
        protected AuthServiceInterface $auth
    ) {}

    /**
     * POST /api/v1/orgs/{rootOrgId}/officers
     *
     * Creates a fully active User account + root org membership +
     * optional branch membership in one atomic transaction.
     * The officer can log in immediately with the provided credentials.
     */
    public function store(Request $request, string $rootOrgId): JsonResponse
    {
        $request->validate([
            'full_name'            => ['required', 'string', 'max:255'],
            'email'                => ['required', 'string', 'email', 'max:255', 'unique:platform.users,email'],
            'password'             => ['required', 'confirmed', 'min:8'],
            'phone'                => ['nullable', 'string', 'max:30'],
            'org_role_id'          => ['required', 'string', 'size:26', 'exists:platform.org_roles,id'],
            'level'                => ['nullable', 'integer', 'min:0', 'max:99'],
            'branch_id'            => ['nullable', 'string', 'size:26', 'exists:platform.organizations,id'],
        ]);

        // Root org must exist, be a root, and be active
        $rootOrg = Organization::where('id', $rootOrgId)
            ->where('type', 'root')
            ->where('status', 'active')
            ->first();

        if (!$rootOrg) {
            return response()->json([
                'message' => 'Organization not found or not active.',
            ], 404);
        }

        // Role must belong to this root org tree
        $role = OrgRole::where('id', $request->org_role_id)
            ->where('root_org_id', $rootOrgId)
            ->first();

        if (!$role) {
            return response()->json([
                'message' => 'Role does not belong to this organization.',
            ], 422);
        }

        // Validate branch if provided
        $branchId = null;
        if ($request->filled('branch_id') && $request->branch_id !== $rootOrgId) {
            $branch = Organization::where('id', $request->branch_id)
                ->where('root_org_id', $rootOrgId)
                ->where('status', 'active')
                ->first();

            if (!$branch) {
                return response()->json([
                    'message' => 'Branch not found or does not belong to this organization.',
                ], 422);
            }

            $branchId = $request->branch_id;
        }

        $level = (int) ($request->level ?? 0);

        try {
            $result = DB::connection('platform')->transaction(function () use (
                $request, $rootOrgId, $branchId, $level
            ) {
                // 1. Generate unique username from full_name
                $username = $this->generateUsername($request->full_name);

                // 2. Create User + Actor via AuthService
                //    actor.display_name = full_name (the real displayed name)
                $user = $this->auth->register([
                    'name'     => $request->full_name,
                    'username' => $username,
                    'email'    => $request->email,
                    'password' => $request->password,
                ]);

                // 3. Always create root org membership first (anchor for transfers)
                $rootMembership = OrgMembership::create([
                    'user_id'     => $user->id,
                    'org_id'      => $rootOrgId,
                    'org_role_id' => $request->org_role_id,
                    'level'       => $level,
                    'invited_by'  => $request->user()->id,
                    'status'      => 'active',
                    'joined_at'   => now(),
                ]);

                // 4. Optionally create branch membership
                $activeMembership = $rootMembership;
                if ($branchId) {
                    $branchMembership = OrgMembership::create([
                        'user_id'     => $user->id,
                        'org_id'      => $branchId,
                        'org_role_id' => $request->org_role_id,
                        'level'       => $level,
                        'invited_by'  => $request->user()->id,
                        'status'      => 'active',
                        'joined_at'   => now(),
                    ]);
                    $activeMembership = $branchMembership;
                }

                return $activeMembership->load(['user.actor', 'orgRole', 'organization']);
            });
        } catch (\Throwable $e) {
            Log::error('OfficerController@store failed', [
                'error'       => $e->getMessage(),
                'root_org_id' => $rootOrgId,
                'email'       => $request->email,
            ]);

            // Return a clear 422 for unique constraint violations on email
            if (str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'Duplicate')) {
                return response()->json([
                    'message' => 'An account with this email already exists.',
                ], 422);
            }

            return response()->json([
                'message' => 'Failed to create officer: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Officer account created successfully.',
            'officer' => $this->formatOfficer($result),
        ], 201);
    }

    /**
     * POST /api/v1/orgs/{rootOrgId}/officers/{userId}/transfer
     */
    public function transfer(Request $request, string $rootOrgId, string $userId): JsonResponse
    {
        $request->validate([
            'from_branch_id' => ['required', 'string', 'size:26'],
            'to_branch_id'   => ['required', 'string', 'size:26'],
            'org_role_id'    => ['required', 'string', 'size:26', 'exists:platform.org_roles,id'],
        ]);

        if ($request->from_branch_id === $request->to_branch_id) {
            return response()->json([
                'message' => 'from_branch_id and to_branch_id must be different.',
            ], 422);
        }

        $rootOrg = Organization::where('id', $rootOrgId)->where('type', 'root')->first();
        if (!$rootOrg) {
            return response()->json(['message' => 'Root organization not found.'], 404);
        }

        $targetBranch = Organization::where('id', $request->to_branch_id)
            ->where('root_org_id', $rootOrgId)
            ->where('status', 'active')
            ->first();

        if (!$targetBranch) {
            return response()->json([
                'message' => 'Target branch not found or does not belong to this organization.',
            ], 422);
        }

        try {
            DB::connection('platform')->transaction(function () use ($request, $rootOrgId, $userId) {
                $fromBranchId = $request->from_branch_id;
                $toBranchId   = $request->to_branch_id;

                // Self-heal: ensure root org membership exists
                $rootMembership = OrgMembership::where('user_id', $userId)
                    ->where('org_id', $rootOrgId)
                    ->where('status', 'active')
                    ->first();

                if (!$rootMembership) {
                    $sourceMembership = OrgMembership::where('user_id', $userId)
                        ->where('org_id', $fromBranchId)
                        ->first();

                    $rootMembership = OrgMembership::create([
                        'user_id'     => $userId,
                        'org_id'      => $rootOrgId,
                        'org_role_id' => $sourceMembership?->org_role_id ?? $request->org_role_id,
                        'level'       => $sourceMembership?->level ?? 0,
                        'invited_by'  => $request->user()->id,
                        'status'      => 'active',
                        'joined_at'   => now(),
                    ]);
                }

                // Remove old branch membership only (never root)
                if ($fromBranchId !== $rootOrgId) {
                    OrgMembership::where('user_id', $userId)
                        ->where('org_id', $fromBranchId)
                        ->delete();
                }

                // Create new branch membership (or re-activate if it exists)
                $existing = OrgMembership::where('user_id', $userId)
                    ->where('org_id', $toBranchId)
                    ->where('org_role_id', $request->org_role_id)
                    ->first();

                if ($existing) {
                    if ($existing->status !== 'active') {
                        $existing->update(['status' => 'active', 'joined_at' => now()]);
                    }
                    return;
                }

                OrgMembership::create([
                    'user_id'     => $userId,
                    'org_id'      => $toBranchId,
                    'org_role_id' => $request->org_role_id,
                    'level'       => $rootMembership->level,
                    'invited_by'  => $request->user()->id,
                    'status'      => 'active',
                    'joined_at'   => now(),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('OfficerController@transfer failed', [
                'error'       => $e->getMessage(),
                'root_org_id' => $rootOrgId,
                'user_id'     => $userId,
            ]);

            return response()->json([
                'message' => 'Transfer failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json(['message' => 'Officer transferred successfully.']);
    }

    // ── Helpers ────────────────────────────────────────────────

    private function formatOfficer(OrgMembership $membership): array
    {
        $user  = $membership->user;
        $actor = $user?->actor;
        $role  = $membership->orgRole;
        $org   = $membership->organization;

        return [
            'user_id'       => $user?->id ?? '',
            'actor_id'      => $user?->actor_id ?? '',
            'name'          => $actor?->display_name ?? $user?->username ?? '',
            'username'      => $user?->username ?? '',
            'email'         => $user?->email ?? '',
            'user_status'   => $user?->status ?? 'active',
            'org_id'        => $membership->org_id,
            'org_name'      => $org?->name ?? '',
            'org_role_id'   => $role?->id ?? '',
            'org_role_name' => $role?->name ?? '',
            'level'         => $membership->level,
            'status'        => $membership->status,
            'created_at'    => $membership->created_at?->toISOString(),
        ];
    }

    private function generateUsername(string $fullName): string
    {
        $base      = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $fullName) ?? $fullName);
        $base      = trim($base);
        $base      = preg_replace('/\s+/', '_', $base) ?? $base;
        $base      = substr($base, 0, 40);
        $candidate = $base . '_' . strtolower(Str::random(4));
        $i         = 1;

        while (User::where('username', $candidate)->exists()) {
            $candidate = $base . '_' . strtolower(Str::random(4)) . "_{$i}";
            $i++;
        }

        return $candidate;
    }
}