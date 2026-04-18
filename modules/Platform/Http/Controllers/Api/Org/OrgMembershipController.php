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
use Modules\Platform\Models\OrgMembership;
use Modules\Platform\Models\OrgRole;
use Modules\Platform\Models\Organization;

class OrgMembershipController extends Controller
{
    public function __construct(
        protected OrganizationServiceInterface $orgService
    ) {}

    /** GET /api/v1/orgs/{orgId}/members */
    public function index(Request $request, string $orgId): JsonResponse
    {
        $org = \Modules\Platform\Models\Organization::findOrFail($orgId);
 
        // For root orgs: collect all org IDs in the tree so branch
        // members also appear in the listing.
        if ($org->type === 'root') {
            $treeOrgIds = \Modules\Platform\Models\Organization::where(
                    'root_org_id', $orgId
                )
                ->orWhere('id', $orgId)
                ->pluck('id');
 
            $members = \Modules\Platform\Models\OrgMembership::whereIn(
                    'org_id', $treeOrgIds
                )
                ->with(['user.actor', 'orgRole', 'organization'])
                ->when(
                    $request->get('status'),
                    fn($q, $v) => $q->where('status', $v)
                )
                ->when(
                    $request->get('branch_id'),
                    fn($q, $v) => $q->where('org_id', $v)
                )
                ->orderByDesc('level')
                ->paginate((int) $request->get('per_page', 50));
        } else {
            // Branch: only members of this specific branch node
            $members = \Modules\Platform\Models\OrgMembership::where(
                    'org_id', $orgId
                )
                ->with(['user.actor', 'orgRole', 'organization'])
                ->when(
                    $request->get('status'),
                    fn($q, $v) => $q->where('status', $v)
                )
                ->orderByDesc('level')
                ->paginate((int) $request->get('per_page', 50));
        }
 
        return response()->json($members);
    }

    /** POST /api/v1/orgs/{orgId}/members/invite */
    public function invite(InviteMemberRequest $request, string $orgId): JsonResponse
    {
        $invitation = $this->orgService->inviteMember(
            $orgId,
            $request->email,
            $request->org_role_id,
            $request->level ?? 0,
            $request->user()->id
        );

        // ── Send invitation email ─────────────────────────────
        try {
            $org = Organization::find($orgId);
            $inviterName = $request->user()->name
                ?? $request->user()->username
                ?? $request->user()->email;

            $invitation->load('role');

            Mail::to($request->email)->send(
                new OrgInvitationMail(
                    invitation: $invitation,
                    orgName: $org->name ?? 'Organization',
                    inviterName: $inviterName,
                )
            );
        } catch (\Throwable $e) {
            \Log::warning("Failed to send invitation email to {$request->email}: {$e->getMessage()}");
        }

        return response()->json([
            'message'    => 'Invitation sent.',
            'invitation' => $invitation,
        ], 201);
    }

    /**
     * POST /api/v1/orgs/{orgId}/members/assign — NEW
     *
     * Directly assign an existing root org member to a branch.
     * No invitation required — the user is already a verified member
     * of the org tree. Creates an active membership at the branch.
     */
    public function assign(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'user_id'     => ['required', 'string', 'size:26'],
            'org_role_id' => ['required', 'string', 'size:26'],
            'level'       => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        $branch = Organization::findOrFail($orgId);

        // Must be a branch, not root
        if ($branch->type === 'root') {
            return response()->json([
                'message' => 'Use the invite endpoint for root org members.',
            ], 422);
        }

        // Verify the user is an active member of the root org
        $rootOrgId = $branch->root_org_id;
        $rootMembership = OrgMembership::where('user_id', $request->user_id)
            ->where('org_id', $rootOrgId)
            ->where('status', 'active')
            ->first();

        if (!$rootMembership) {
            return response()->json([
                'message' => 'User must be an active member of the root organization first.',
            ], 422);
        }

    // Check if already assigned to this branch (any role, any status)
    $existing = OrgMembership::where('user_id', $request->user_id)
        ->where('org_id', $orgId)
        ->first();  // ← removed ->where('status', 'active')

    if ($existing) {
        if ($existing->status === 'active') {
            return response()->json([
                'message' => 'User is already an active member of this branch.',
            ], 422);
        }
        // Reactivate suspended membership instead of creating duplicate
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

        // Verify role exists
        OrgRole::findOrFail($request->org_role_id);

        // Create active membership directly
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

    /** POST /api/v1/orgs/invitations/{token}/accept */
    public function accept(Request $request, string $token): JsonResponse
    {
        $membership = $this->orgService->acceptInvitation($token, $request->user()->id);

        return response()->json([
            'message'    => 'Invitation accepted.',
            'membership' => $membership,
        ]);
    }

    /** DELETE /api/v1/orgs/{orgId}/members/{userId} */
    public function remove(Request $request, string $orgId, string $userId): JsonResponse
    {
        $this->orgService->removeMember($orgId, $userId, $request->user()->id);
        return response()->json(['message' => 'Member removed.']);
    }

    /** PATCH /api/v1/orgs/{orgId}/members/{userId} */
    public function update(UpdateMemberRequest $request, string $orgId, string $userId): JsonResponse
    {
        $membership = $this->orgService->updateMember(
            $orgId, $userId, $request->validated(), $request->user()->id
        );

        return response()->json(['message' => 'Member updated.', 'membership' => $membership]);
    }
    /** GET /api/v1/orgs/{orgId}/members/{userId} */
    public function show(Request $request, string $orgId, string $userId): JsonResponse
    {
        $membership = \Modules\Platform\Models\OrgMembership::where('org_id', $orgId)
            ->where('user_id', $userId)
            ->with(['user.actor', 'orgRole', 'organization'])
            ->firstOrFail();

        return response()->json($membership);
    }
}