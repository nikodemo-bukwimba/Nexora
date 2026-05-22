<?php
// FILE: modules/Platform/Http/Controllers/Api/Org/OrgMembershipController.php
//
// CHANGES — three fixes, all in index() and show():
//
// FIX 1 — index() for root org:
//   Was returning ALL memberships across the tree including root org rows.
//   Flutter picked up the root membership (branch_manager role, root org_id)
//   instead of the branch membership. Now returns only the MOST RELEVANT
//   membership per user: branch membership preferred over root membership.
//
// FIX 2 — show():
//   Was querying by org_id from the URL. If the URL had root org id but the
//   officer's active membership is on a branch, it returned the root row →
//   wrong role, wrong branch. Now resolves the officer's CURRENT branch from
//   pm_officers first, then returns that membership enriched with branch data.
//
// FIX 3 — both methods:
//   Response now includes branch_id and branch_name from pm_officers so
//   OfficerModel.fromJson() can read the correct branch without falling
//   back to org_id (which is always the membership's org, not the branch).

namespace Modules\Platform\Http\Controllers\Api\Org;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Modules\Platform\Contracts\Services\OrganizationServiceInterface;
use Modules\Platform\Http\Requests\Org\InviteMemberRequest;
use Modules\Platform\Http\Requests\Org\UpdateMemberRequest;
use Modules\Platform\Mail\OrgInvitationMail;
use Modules\Platform\Models\OrgInvitation;
use Modules\Platform\Models\OrgMembership;
use Modules\Platform\Models\OrgRole;
use Modules\Platform\Models\Organization;
use Modules\PharmaMarketing\Models\PmOfficer;

class OrgMembershipController extends Controller
{
    public function __construct(
        protected OrganizationServiceInterface $orgService
    ) {}

    // ── GET /api/v1/orgs/{orgId}/members ─────────────────────────────────────
    public function index(Request $request, string $orgId): JsonResponse
    {
        $org = Organization::findOrFail($orgId);

        if ($org->type === 'root') {
            $treeOrgIds = Organization::where('root_org_id', $orgId)
                ->orWhere('id', $orgId)
                ->pluck('id');

            // ── FIX 1 ────────────────────────────────────────────────────────
            // For root org queries, get ALL memberships but then deduplicate
            // per user — keeping the BRANCH membership over the root membership.
            // This prevents Flutter from picking up the root row (branch_manager)
            // instead of the actual branch assignment.
            $allMemberships = OrgMembership::whereIn('org_id', $treeOrgIds)
                ->with(['user.actor', 'orgRole', 'organization'])
                ->when($request->get('status'),    fn($q, $v) => $q->where('status', $v))
                ->when($request->get('branch_id'), fn($q, $v) => $q->where('org_id', $v))
                ->orderByDesc('level')
                ->get();

            // ── Filter out customer/viewer/user roles — officers only ──────────
            // Self-registered customers also get org memberships and would appear
            // in this list without this filter. Only show officer/admin roles.
            $officerRoleSlugs = ['officer', 'branch_manager', 'owner', 'admin',
                                 'field_officer', 'org_admin', 'junior_officer'];
            $allMemberships = $allMemberships->filter(function ($m) use ($officerRoleSlugs) {
                $slug = $m->orgRole?->slug ?? '';
                // Exclude known customer roles
                if (in_array($slug, ['customer', 'user', 'viewer'])) {
                    return false;
                }
                // Include if slug matches officer roles OR if no slug (custom roles)
                // Custom roles default to showing — customer roles are explicitly excluded
                return true;
            });
            // ─────────────────────────────────────────────────────────────────────

            // Deduplicate: prefer branch membership (org_id != rootOrgId) over root
            $deduped = $allMemberships
                ->groupBy('user_id')
                ->map(function ($userMemberships) use ($orgId) {
                    // Try to find a non-root membership first
                    $branch = $userMemberships->first(fn($m) => $m->org_id !== $orgId);
                    return $branch ?? $userMemberships->first();
                })
                ->values();

            // Enrich each membership with pm_officers branch data
            $enriched = $deduped->map(fn($m) => $this->enrich($m, $orgId));

            // Re-paginate manually to keep the same response shape
            $page    = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 50);
            $slice   = $enriched->forPage($page, $perPage)->values();

            return response()->json([
                'data'          => $slice,
                'current_page'  => $page,
                'per_page'      => $perPage,
                'total'         => $enriched->count(),
                'last_page'     => (int) ceil($enriched->count() / $perPage),
            ]);
            // ─────────────────────────────────────────────────────────────────
        }

        // Branch-scoped query — unchanged logic, just add enrichment
        $members = OrgMembership::where('org_id', $orgId)
            ->with(['user.actor', 'orgRole', 'organization'])
            ->when($request->get('status'), fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('level')
            ->paginate((int) $request->get('per_page', 50));

        $members->getCollection()->transform(
            fn($m) => $this->enrich($m, $org->root_org_id ?? $orgId)
        );

        return response()->json($members);
    }

    // ── GET /api/v1/orgs/{orgId}/members/{userId} ────────────────────────────
    public function show(Request $request, string $orgId, string $userId): JsonResponse
    {
        // ── FIX 2 (updated) ──────────────────────────────────────────────────
        // The orgId in the URL is always the ROOT org (that's what the admin
        // app sends). But the user's membership may be on a BRANCH, not root —
        // this is the case for invitation-accepted officers and in-app created
        // officers. The old firstOrFail() with org_id=rootOrgId returned 404
        // for anyone whose only active membership is on a branch.
        //
        // Resolution order:
        //   1. Check pm_officers → get current branch_id → find that membership
        //   2. No pm_officers record → search entire org tree for any active membership
        //   3. Nothing found → 404

        $org       = Organization::findOrFail($orgId);
        $rootOrgId = $org->root_org_id ?? $orgId;

        // All org ids in this tree (root + all branches)
        $treeOrgIds = Organization::where('root_org_id', $rootOrgId)
            ->orWhere('id', $rootOrgId)
            ->pluck('id');

        // Step 1: check pm_officers for current branch
        $pmOfficer   = PmOfficer::where('platform_user_id', $userId)
            ->where('org_id', $rootOrgId)
            ->first();
        $targetOrgId = $pmOfficer?->branch_id;

        // Step 2: find the best membership
        if ($targetOrgId) {
            // pm_officers knows the branch — fetch that branch membership
            $membership = OrgMembership::where('user_id', $userId)
                ->where('org_id', $targetOrgId)
                ->where('status', 'active')
                ->with(['user.actor', 'orgRole', 'organization'])
                ->first();
        } else {
            // No pm_officers record (invitation-accepted, pre-fix officers):
            // find any active membership in the tree, preferring branch over root
            $allMemberships = OrgMembership::where('user_id', $userId)
                ->whereIn('org_id', $treeOrgIds)
                ->where('status', 'active')
                ->with(['user.actor', 'orgRole', 'organization'])
                ->get();

            // Prefer branch membership over root
            $membership = $allMemberships->first(
                fn($m) => $m->organization?->type !== 'root'
            ) ?? $allMemberships->first();
        }

        // Step 3: hard 404 only if truly nothing found anywhere in the tree
        if (! $membership) {
            return response()->json(['message' => 'Member not found.'], 404);
        }
        // ─────────────────────────────────────────────────────────────────────

        return response()->json($this->enrich($membership, $rootOrgId));
    }

    // ── POST /api/v1/orgs/{orgId}/members/invite ─────────────────────────────
    // UNCHANGED
    public function invite(InviteMemberRequest $request, string $orgId): JsonResponse
    {
        $invitation = $this->orgService->inviteMember(
            $orgId,
            $request->email,
            $request->org_role_id,
            $request->level ?? 0,
            $request->user()->id
        );

        if ($request->filled('app_password') && $request->filled('email')) {
            try {
                $authService    = app(\Modules\Platform\Contracts\Services\AuthServiceInterface::class);
                $officerService = app(\Modules\PharmaMarketing\Services\OfficerService::class);

                $existingUser = \Modules\Platform\Models\User::where('email', $request->email)->first();

                if (! $existingUser) {
                    $username     = \Illuminate\Support\Str::slug($request->email) . '_' . substr(uniqid(), -4);
                    $existingUser = $authService->register([
                        'name'     => $request->name ?? $request->email,
                        'username' => $username,
                        'email'    => $request->email,
                        'password' => $request->app_password,
                    ]);
                }

                OrgMembership::firstOrCreate(
                    ['user_id' => $existingUser->id, 'org_id' => $orgId],
                    [
                        'org_role_id' => $request->org_role_id,
                        'level'       => $request->level ?? 0,
                        'invited_by'  => $request->user()->id,
                        'status'      => 'active',
                        'joined_at'   => now(),
                    ]
                );

                $rootOrgId = Organization::findOrFail($orgId)->root_org_id ?? $orgId;
                $officerService->createFromAdminOrg(
                    orgId:          $rootOrgId,
                    branchId:       $orgId,
                    platformUserId: $existingUser->id,
                    actorId:        $existingUser->actor_id ?? '',
                    name:           $existingUser->actor?->display_name ?? $existingUser->username,
                    email:          $existingUser->email,
                    phone:          $request->phone,
                    source:         'admin',
                );

                $invitation->update(['status' => 'accepted']);
            // Note: for invitation-accepted officers (no app_password),
            // pm_officers record is created by OrgService::acceptInvitation()
            // — see the accept() method below which calls createFromAdminOrg.
            } catch (\Throwable $e) {
                \Log::warning("Officer app_password setup failed: {$e->getMessage()}");
            }
        }

        try {
            $org         = Organization::find($orgId);
            $inviterName = $request->user()->name
                ?? $request->user()->username
                ?? $request->user()->email;

            $invitation->load('role');

            Mail::to($request->email)->send(
                new OrgInvitationMail(
                    invitation:  $invitation,
                    orgName:     $org->name ?? 'Organization',
                    inviterName: $inviterName,
                )
            );
        } catch (\Throwable $e) {
            \Log::warning("Failed to send invitation email to {$request->email}: {$e->getMessage()}");
        }

        return response()->json([
            'message'    => 'Invitation sent.',
            'invitation' => $invitation->fresh(['role']),
        ], 201);
    }

    // ── GET /api/v1/orgs/{orgId}/invitations ─────────────────────────────────
    // UNCHANGED
    public function invitations(Request $request, string $orgId): JsonResponse
    {
        $org = Organization::findOrFail($orgId);

        $orgIds = Organization::where('root_org_id', $org->root_org_id ?? $orgId)
            ->orWhere('id', $orgId)
            ->pluck('id');

        $status = $request->get('status', 'pending');

        $invitations = OrgInvitation::whereIn('org_id', $orgIds)
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->with(['role:id,name,slug', 'organization:id,name,type'])
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 50));

        return response()->json($invitations);
    }

    // ── DELETE /api/v1/orgs/{orgId}/invitations/{invitationId}/cancel ─────────
    // UNCHANGED
    public function cancelInvitation(
        Request $request,
        string $orgId,
        string $invitationId
    ): JsonResponse {
        $invitation = OrgInvitation::where('id', $invitationId)
            ->where('status', 'pending')
            ->firstOrFail();

        $invitation->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Invitation cancelled.']);
    }

    // ── POST /api/v1/orgs/{orgId}/members/assign ─────────────────────────────
    // UNCHANGED
    public function assign(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'user_id'     => ['required', 'string', 'size:26'],
            'org_role_id' => ['required', 'string', 'size:26'],
            'level'       => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        $branch = Organization::findOrFail($orgId);

        if ($branch->type === 'root') {
            return response()->json([
                'message' => 'Use the invite endpoint for root org members.',
            ], 422);
        }

        $rootOrgId      = $branch->root_org_id;
        $rootMembership = OrgMembership::where('user_id', $request->user_id)
            ->where('org_id', $rootOrgId)
            ->where('status', 'active')
            ->first();

        if (! $rootMembership) {
            return response()->json([
                'message' => 'User must be an active member of the root organization first.',
            ], 422);
        }

        $existing = OrgMembership::where('user_id', $request->user_id)
            ->where('org_id', $orgId)
            ->first();

        if ($existing) {
            if ($existing->status === 'active') {
                return response()->json([
                    'message' => 'User is already an active member of this branch.',
                ], 422);
            }
            $existing->update([
                'org_role_id' => $request->org_role_id,
                'level'       => $request->level ?? $rootMembership->level,
                'status'      => 'active',
                'joined_at'   => now(),
            ]);
            return response()->json([
                'message'    => 'Member reassigned to branch.',
                'membership' => $existing->fresh(['user', 'orgRole']),
            ], 200);
        }

        OrgRole::findOrFail($request->org_role_id);

        $membership = OrgMembership::create([
            'user_id'     => $request->user_id,
            'org_id'      => $orgId,
            'org_role_id' => $request->org_role_id,
            'level'       => $request->level ?? $rootMembership->level,
            'invited_by'  => $request->user()->id,
            'status'      => 'active',
            'joined_at'   => now(),
        ]);

        return response()->json([
            'message'    => 'Member assigned to branch.',
            'membership' => $membership->fresh(['user', 'orgRole']),
        ], 201);
    }

    // ── POST /api/v1/orgs/invitations/{token}/accept ──────────────────────────
    //
    // CHANGE: After accepting, create pm_officers record so the officer:
    //   1. Appears correctly in officer detail (not as customer/root org)
    //   2. Can be branch-transferred
    //   3. Has branch_id resolved by /auth/me
    public function accept(Request $request, string $token): JsonResponse
    {
        try {
            $membership = $this->orgService->acceptInvitation(
                $token,
                $request->user()->id
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'message' => 'Invalid or already used invitation token.',
            ], 404);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }

        // ── Create pm_officers record ─────────────────────────────────────────
        try {
            $officerService = app(\Modules\PharmaMarketing\Services\OfficerService::class);
            $user           = $request->user()->load('actor');
            $orgId          = is_object($membership) ? $membership->org_id ?? null : null;

            if ($orgId) {
                $org = Organization::find($orgId);
                if ($org) {
                    $rootOrgId = $org->root_org_id ?? $org->id;
                    $branchId  = $org->type === 'branch' ? $org->id : $rootOrgId;

                    $officerService->createFromAdminOrg(
                        orgId:          $rootOrgId,
                        branchId:       $branchId,
                        platformUserId: $user->id,
                        actorId:        $user->actor_id ?? '',
                        name:           $user->actor?->display_name ?? $user->username,
                        email:          $user->email,
                        phone:          null,
                        source:         'invitation',
                    );
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'accept(): failed to create pm_officers record: ' . $e->getMessage()
            );
            // Non-fatal — membership was created, officer record is best-effort
        }

        return response()->json([
            'message'    => 'You have successfully joined the organization.',
            'membership' => $membership,
        ]);
    }

    // ── DELETE /api/v1/orgs/{orgId}/members/{userId} ──────────────────────────
    // UNCHANGED
    public function remove(Request $request, string $orgId, string $userId): JsonResponse
    {
        $this->orgService->removeMember($orgId, $userId, $request->user()->id);

        return response()->json(['message' => 'Member removed.']);
    }

    // ── PATCH /api/v1/orgs/{orgId}/members/{userId} ───────────────────────────
    // UNCHANGED
    public function update(UpdateMemberRequest $request, string $orgId, string $userId): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('Officer update request', [
            'org_id'  => $orgId,
            'user_id' => $userId,
            'body'    => $request->all(),
        ]);

        // ── Resolve root org FIRST — needed for both pm_officers and enrich() ──
        $org       = \Modules\Platform\Models\Organization::find($orgId);
        $rootOrgId = $org?->root_org_id ?? $orgId;
        // ────────────────────────────────────────────────────────────────────────

        $membership = $this->orgService->updateMember(
            $orgId,
            $userId,
            $request->validated(),
            $request->user()->id
        );

        // ── Update pm_officers name/phone ─────────────────────────────────────
        $name  = $request->input('name');
        $phone = $request->input('phone');

        if ($name || $phone) {
            \Illuminate\Support\Facades\Log::info('Updating pm_officers', [
                'root_org_id' => $rootOrgId,
                'user_id'     => $userId,
                'name'        => $name,
                'phone'       => $phone,
            ]);

            $updated = \Modules\PharmaMarketing\Models\PmOfficer::where('platform_user_id', $userId)
                ->where('org_id', $rootOrgId)
                ->update(array_filter([
                    'name'  => $name,
                    'phone' => $phone,
                ], fn($v) => $v !== null));

            \Illuminate\Support\Facades\Log::info('pm_officers rows updated: ' . $updated);
        }
        // ─────────────────────────────────────────────────────────────────────

        return response()->json([
            'message'    => 'Member updated.',
            'membership' => $this->enrich($membership, $rootOrgId),
        ]);
    }

    // ── Private: enrich a membership with pm_officers branch data ────────────
    //
    // Adds branch_id, branch_name, and the correct role from the officer's
    // CURRENT branch membership — not from the root org membership row.
    //
    // This is the single source of truth fix: pm_officers.branch_id always
    // reflects the current branch after a transfer. Without this enrichment,
    // Flutter reads org_id (root org) and org_role (branch_manager) from the
    // root membership row, ignoring the actual branch assignment entirely.
    private function enrich(OrgMembership $membership, string $rootOrgId): array
    {
        $user  = $membership->user;
        $actor = $user?->actor;

        // Resolve pm_officer for this user
        $pmOfficer = PmOfficer::where('platform_user_id', $user?->id)
            ->where('org_id', $rootOrgId)
            ->first();

        // Current branch from pm_officers (null for non-officers / HQ admins)
        $branchId   = $pmOfficer?->branch_id;
        $branchName = null;

        // Resolve the branch membership to get the correct role
        $branchMembership = null;
        if ($branchId) {
            $branch     = Organization::find($branchId);
            $branchName = $branch?->name;

            // Get the membership for the branch (not root) to get the real role
            $branchMembership = OrgMembership::where('user_id', $user?->id)
                ->where('org_id', $branchId)
                ->where('status', 'active')
                ->with('orgRole')
                ->first();
        }

        // Use branch role if found, otherwise fall back to the passed membership
        $effectiveMembership = $branchMembership ?? $membership;
        $role                = $effectiveMembership->orgRole ?? $membership->orgRole;

        return [
            'user_id'        => $user?->id ?? '',
            'actor_id'       => $user?->actor_id ?? $actor?->id ?? '',
            'name'           => $actor?->display_name ?? $user?->username ?? '',
            'username'       => $user?->username ?? '',
            'email'          => $user?->email ?? '',
            'phone'          => $pmOfficer?->phone ?? $user?->actor?->phone ?? null,
            'user_status'    => $user?->status ?? 'active',
            // org_id = membership's org (root or branch, as originally stored)
            'org_id'         => $membership->org_id,
            'org_name'       => $membership->organization?->name ?? '',
            // branch_id/branch_name = current branch from pm_officers ← THE FIX
            'branch_id'      => $branchId ?? $membership->org_id,
            'branch_name'    => $branchName ?? $membership->organization?->name ?? '',
            'org_role_id'    => $role?->id ?? '',
            'org_role_name'  => $role?->name ?? '',
            'role'           => [
                'id'   => $role?->id ?? '',
                'name' => $role?->name ?? '',
            ],
            'level'          => $effectiveMembership->level ?? $membership->level ?? 0,
            'status'         => $effectiveMembership->status ?? $membership->status,
            'created_at'     => $membership->created_at?->toISOString(),
        ];
    }
}
