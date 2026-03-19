<?php

namespace Modules\Platform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\OrgMembershipServiceInterface;

class OrgMembershipController extends Controller
{
    public function __construct(
        protected OrgMembershipServiceInterface $memberships
    ) {}

    /** GET /api/v1/orgs/{orgId}/members */
    public function index(Request $request, string $orgId): JsonResponse
    {
        $members = $this->memberships->listMembers(
            $orgId,
            $request->only(['status']),
            (int) $request->get('per_page', 25)
        );

        return response()->json($members);
    }

    /** POST /api/v1/orgs/{orgId}/members/invite */
    public function invite(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'user_id'     => ['required', 'string', 'size:26', 'exists:platform.users,id'],
            'org_role_id' => ['required', 'string', 'size:26', 'exists:platform.org_roles,id'],
            'level'       => ['nullable', 'integer', 'min:0', 'max:99'], // max 99 — 100 is special
        ]);

        $membership = $this->memberships->invite(
            $orgId,
            $request->only(['user_id', 'org_role_id', 'level']),
            $request->user()->id
        );

        return response()->json([
            'message'    => 'Invitation sent.',
            'membership' => $membership,
        ], 201);
    }

    /** POST /api/v1/orgs/{orgId}/members/{membershipId}/accept */
    public function accept(Request $request, string $orgId, string $membershipId): JsonResponse
    {
        $membership = $this->memberships->acceptInvite($membershipId, $request->user()->id);

        return response()->json(['message' => 'Invitation accepted.', 'membership' => $membership]);
    }

    /** POST /api/v1/orgs/{orgId}/members/{membershipId}/decline */
    public function decline(Request $request, string $orgId, string $membershipId): JsonResponse
    {
        $membership = $this->memberships->declineInvite($membershipId, $request->user()->id);

        return response()->json(['message' => 'Invitation declined.', 'membership' => $membership]);
    }

    /** DELETE /api/v1/orgs/{orgId}/members/{membershipId} */
    public function remove(Request $request, string $orgId, string $membershipId): JsonResponse
    {
        $this->memberships->removeMember($membershipId, $request->user()->id);

        return response()->json(['message' => 'Member removed.']);
    }

    /** PATCH /api/v1/orgs/{orgId}/members/{membershipId} */
    public function update(Request $request, string $orgId, string $membershipId): JsonResponse
    {
        $request->validate([
            'org_role_id' => ['sometimes', 'string', 'size:26', 'exists:platform.org_roles,id'],
            'level'       => ['sometimes', 'integer', 'min:0', 'max:100'],
            'status'      => ['sometimes', 'string', 'in:active,suspended'],
        ]);

        $membership = $this->memberships->updateMember(
            $membershipId,
            $request->only(['org_role_id', 'level', 'status']),
            $request->user()->id
        );

        return response()->json(['message' => 'Member updated.', 'membership' => $membership]);
    }
}
