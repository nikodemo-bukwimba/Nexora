<?php
// FILE: modules/Platform/Http/Controllers/Api/Org/OfficerController.php
// PATH: D:\Projects\Barick Phamacy\nexora\modules\Platform\Http\Controllers\Api\Org\OfficerController.php
//
// CHANGE: transfer() method only.
//   — Now injects OfficerService and calls transferOfficer() so that
//     pm_officers.branch_id is updated atomically alongside org_memberships.
//   — Without this call, /auth/me always reads the stale branch_id from
//     pm_officers and returns the wrong branch after every transfer.
//   — store() and all helpers are completely unchanged.

namespace Modules\Platform\Http\Controllers\Api\Org;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Platform\Contracts\Services\AuthServiceInterface;
use Modules\Platform\Models\OrgMembership;
use Modules\Platform\Models\OrgRole;
use Modules\Platform\Models\Organization;
use Modules\Platform\Models\User;
// ── NEW ──────────────────────────────────────────────────────────────────────
use Modules\PharmaMarketing\Services\OfficerService;
// ─────────────────────────────────────────────────────────────────────────────

class OfficerController extends Controller
{
    public function __construct(
        protected AuthServiceInterface $auth,
        // ── NEW ──────────────────────────────────────────────────────────────
        protected OfficerService $officerService,
        // ─────────────────────────────────────────────────────────────────────
    ) {}

    /**
     * POST /api/v1/orgs/{rootOrgId}/officers
     * UNCHANGED — copied verbatim.
     */
    public function store(Request $request, string $rootOrgId): JsonResponse
    {
        $request->validate([
            'full_name'   => ['required', 'string', 'max:255'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:platform.users,email'],
            'password'    => ['required', 'confirmed', 'min:8'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'org_role_id' => ['required', 'string', 'size:26', 'exists:platform.org_roles,id'],
            'level'       => ['nullable', 'integer', 'min:0', 'max:99'],
            'branch_id'   => ['nullable', 'string', 'size:26', 'exists:platform.organizations,id'],
        ]);

        $rootOrg = Organization::where('id', $rootOrgId)
            ->where('type', 'root')
            ->where('status', 'active')
            ->first();

        if (!$rootOrg) {
            return response()->json(['message' => 'Organization not found or not active.'], 404);
        }

        $role = OrgRole::where('id', $request->org_role_id)
            ->where('root_org_id', $rootOrgId)
            ->first();

        if (!$role) {
            return response()->json(['message' => 'Role does not belong to this organization.'], 422);
        }

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
                // ── Step 1: Find or create the platform user ──────────────────
                // auth->register() may silently return an existing user in some
                // implementations. Guard here so we never double-register.
                $user = User::where('email', $request->email)->first();

                if (!$user) {
                    $username = $this->generateUsername($request->full_name);
                    $user = $this->auth->register([
                        'name'     => $request->full_name,
                        'username' => $username,
                        'email'    => $request->email,
                        'password' => $request->password,
                    ]);
                }

                // ── Step 2: Ensure root org membership exists (idempotent) ────
                // firstOrCreate so retries and duplicate calls never throw the
                // unique constraint on (user_id, org_id).
                $rootMembership = OrgMembership::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'org_id'  => $rootOrgId,
                    ],
                    [
                        'org_role_id' => $request->org_role_id,
                        'level'       => $level,
                        'invited_by'  => $request->user()->id,
                        'status'      => 'active',
                        'joined_at'   => now(),
                    ]
                );

                $activeMembership = $rootMembership;

                // ── Step 3: Ensure branch membership exists (idempotent) ──────
                if ($branchId) {
                    $branchMembership = OrgMembership::firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'org_id'  => $branchId,
                        ],
                        [
                            'org_role_id' => $request->org_role_id,
                            'level'       => $level,
                            'invited_by'  => $request->user()->id,
                            'status'      => 'active',
                            'joined_at'   => now(),
                        ]
                    );
                    $activeMembership = $branchMembership;

                    // ── Step 4: Ensure pm_officer record exists (idempotent) ──
                    // createFromAdminOrg() is already idempotent internally.
                    $this->officerService->createFromAdminOrg(
                        orgId:          $rootOrgId,
                        branchId:       $branchId,
                        platformUserId: $user->id,
                        actorId:        $user->actor_id,
                        name:           $request->full_name,
                        email:          $user->email,
                        phone:          $request->phone,
                        source:         'admin',
                    );
                }

                return $activeMembership->load(['user.actor', 'orgRole', 'organization']);
            });
        } catch (\Throwable $e) {
            Log::error('OfficerController@store failed', [
                'error'       => $e->getMessage(),
                'root_org_id' => $rootOrgId,
                'email'       => $request->email,
            ]);

            if (str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'Duplicate')) {
                return response()->json(['message' => 'An account with this email already exists.'], 422);
            }

            return response()->json(['message' => 'Failed to create officer: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Officer account created successfully.',
            'officer' => $this->formatOfficer($result),
        ], 201);
    }

    /**
     * POST /api/v1/orgs/{rootOrgId}/officers/{userId}/transfer
     *
     * CHANGE: Now calls OfficerService::transferOfficer() which updates
     * pm_officers.branch_id atomically alongside org_memberships.
     *
     * Root cause of "re-login shows old branch":
     *   - Before: only org_memberships was updated here.
     *   - /auth/me reads branch from pm_officers.branch_id, not org_memberships.
     *   - So pm_officers.branch_id was always stale → old branch returned forever.
     *
     * After this fix:
     *   - OfficerService::transferOfficer() updates pm_officers.branch_id first.
     *   - It also updates org_memberships (marks old as 'transferred', inserts new).
     *   - /auth/me now reads the correct current branch.
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
            // ── CHANGED: delegate entirely to OfficerService::transferOfficer() ──
            //
            // This single call:
            //   1. Updates pm_officers.branch_id + previous_branch_id + transferred_at + transferred_by
            //   2. Marks old org_memberships row as 'transferred'
            //   3. Inserts (or re-activates) new branch org_memberships row with correct org_role_id
            //
            // The old inline transaction that only touched org_memberships is removed.
            $this->officerService->transferOfficer(
                platformUserId: $userId,
                rootOrgId:      $rootOrgId,
                newBranchId:    $request->to_branch_id,
                newOrgRoleId:   $request->org_role_id,
                transferredBy:  $request->user()->id,
            );
            // ─────────────────────────────────────────────────────────────────────

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Officer not found in this organization.',
            ], 404);
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

    // ── Helpers — UNCHANGED ───────────────────────────────────────────────────

    private function formatOfficer(OrgMembership $membership): array
    {
        $user  = $membership->user;
        $actor = $user?->actor;
        $role  = $membership->orgRole;
        $org   = $membership->organization;

        // ── ADD: resolve actual branch from pm_officers ───────────
        $pmOfficer = \Modules\PharmaMarketing\Models\PmOfficer
            ::where('platform_user_id', $user?->id)
            ->where('org_id', $org?->root_org_id ?? $membership->org_id)
            ->first();

        $branchId   = $pmOfficer?->branch_id ?? $membership->org_id;
        $branch     = \Modules\Platform\Models\Organization::find($branchId);
        // ─────────────────────────────────────────────────────────

        return [
            'user_id'       => $user?->id ?? '',
            'actor_id'      => $user?->actor_id ?? '',
            'name'          => $actor?->display_name ?? $user?->username ?? '',
            'username'      => $user?->username ?? '',
            'email'         => $user?->email ?? '',
            'user_status'   => $user?->status ?? 'active',
            'org_id'        => $membership->org_id,
            'org_name'      => $org?->name ?? '',
            'branch_id'     => $branchId,          // ← ADD
            'branch_name'   => $branch?->name ?? '',// ← ADD
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
