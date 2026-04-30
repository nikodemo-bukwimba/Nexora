<?php

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

class OrgMembershipController extends Controller
{
    public function __construct(
        protected OrganizationServiceInterface $orgService
    ) {}

    // ────────────────────────────────────────────────────────────
    // GET /api/v1/orgs/{orgId}/members
    // ────────────────────────────────────────────────────────────
    public function index(Request $request, string $orgId): JsonResponse
    {
        $org = Organization::findOrFail($orgId);

        if ($org->type === 'root') {
            $treeOrgIds = Organization::where('root_org_id', $orgId)
                ->orWhere('id', $orgId)
                ->pluck('id');

            $members = OrgMembership::whereIn('org_id', $treeOrgIds)
                ->with(['user.actor', 'orgRole', 'organization'])
                ->when($request->get('status'),    fn($q, $v) => $q->where('status', $v))
                ->when($request->get('branch_id'), fn($q, $v) => $q->where('org_id', $v))
                ->orderByDesc('level')
                ->paginate((int) $request->get('per_page', 50));
        } else {
            $members = OrgMembership::where('org_id', $orgId)
                ->with(['user.actor', 'orgRole', 'organization'])
                ->when($request->get('status'), fn($q, $v) => $q->where('status', $v))
                ->orderByDesc('level')
                ->paginate((int) $request->get('per_page', 50));
        }

        return response()->json($members);
    }

    // ────────────────────────────────────────────────────────────
    // GET /api/v1/orgs/{orgId}/members/{userId}
    // ────────────────────────────────────────────────────────────
    public function show(Request $request, string $orgId, string $userId): JsonResponse
    {
        $membership = OrgMembership::where('org_id', $orgId)
            ->where('user_id', $userId)
            ->with(['user.actor', 'orgRole', 'organization'])
            ->firstOrFail();

        return response()->json($membership);
    }

    // ────────────────────────────────────────────────────────────
    // POST /api/v1/orgs/{orgId}/members/invite
    //
    // FIX: The $request->validate([...]) block that was previously
    // placed AFTER inviteMember() and mail sending has been removed.
    // It was dead code (ran after the work was done) and used 'role_id'
    // instead of 'org_role_id', causing the 422 "role field is required"
    // error seen on the client. Validation is handled by InviteMemberRequest
    // (the FormRequest) which runs automatically before this method body.
    // ────────────────────────────────────────────────────────────
    public function invite(InviteMemberRequest $request, string $orgId): JsonResponse
    {
        $invitation = $this->orgService->inviteMember(
            $orgId,
            $request->email,
            $request->org_role_id,
            $request->level ?? 0,
            $request->user()->id
        );

        // ── Optional: immediate account creation (app_password flow) ──
        // If the admin provides app_password, the officer account is
        // created and activated immediately without requiring the user
        // to accept via the invitation token.
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

                // Auto-accept the invitation for this user
                OrgMembership::create([
                    'user_id'     => $existingUser->id,
                    'org_id'      => $orgId,
                    'org_role_id' => $request->org_role_id,
                    'level'       => $request->level ?? 0,
                    'invited_by'  => $request->user()->id,
                    'status'      => 'active',
                    'joined_at'   => now(),
                ]);

                // Create pm_officers record
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
            } catch (\Throwable $e) {
                \Log::warning("Officer app_password setup failed: {$e->getMessage()}");
            }
        }

        // ── Send invitation email ──────────────────────────────
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

    // ────────────────────────────────────────────────────────────
    // GET /api/v1/orgs/{orgId}/invitations
    //
    // Returns all invitations for the org tree so admins can see
    // and re-share tokens without needing to re-invite.
    //
    // Query params:
    //   status   = pending | accepted | expired | cancelled | all
    //              (default: pending)
    //   per_page (default: 50)
    // ────────────────────────────────────────────────────────────
    public function invitations(Request $request, string $orgId): JsonResponse
    {
        $org = Organization::findOrFail($orgId);

        // Scope to entire tree (root + all branches)
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

    // ────────────────────────────────────────────────────────────
    // DELETE /api/v1/orgs/{orgId}/invitations/{invitationId}/cancel
    // ────────────────────────────────────────────────────────────
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

    // ────────────────────────────────────────────────────────────
    // POST /api/v1/orgs/{orgId}/members/assign
    //
    // Directly assign an existing root org member to a branch.
    // Reactivates suspended memberships instead of creating duplicates.
    // ────────────────────────────────────────────────────────────
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

        // Check for any existing membership (any role, any status)
        $existing = OrgMembership::where('user_id', $request->user_id)
            ->where('org_id', $orgId)
            ->first();

        if ($existing) {
            if ($existing->status === 'active') {
                return response()->json([
                    'message' => 'User is already an active member of this branch.',
                ], 422);
            }
            // Reactivate suspended membership instead of creating a duplicate
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

    // ────────────────────────────────────────────────────────────
    // POST /api/v1/orgs/invitations/{token}/accept
    // ────────────────────────────────────────────────────────────
    public function accept(Request $request, string $token): JsonResponse
    {
        $membership = $this->orgService->acceptInvitation(
            $token,
            $request->user()->id
        );

        return response()->json([
            'message'    => 'Invitation accepted.',
            'membership' => $membership,
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // DELETE /api/v1/orgs/{orgId}/members/{userId}
    // ────────────────────────────────────────────────────────────
    public function remove(Request $request, string $orgId, string $userId): JsonResponse
    {
        $this->orgService->removeMember($orgId, $userId, $request->user()->id);

        return response()->json(['message' => 'Member removed.']);
    }

    // ────────────────────────────────────────────────────────────
    // PATCH /api/v1/orgs/{orgId}/members/{userId}
    // ────────────────────────────────────────────────────────────
    public function update(UpdateMemberRequest $request, string $orgId, string $userId): JsonResponse
    {
        $membership = $this->orgService->updateMember(
            $orgId,
            $userId,
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'message'    => 'Member updated.',
            'membership' => $membership,
        ]);
    }
}