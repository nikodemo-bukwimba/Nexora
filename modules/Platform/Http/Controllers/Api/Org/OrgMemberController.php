<?php

namespace Modules\Platform\Http\Controllers\Api\Org;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\OrgMembershipServiceInterface;

class OrgMemberController extends Controller
{
    public function __construct(
        protected OrgMembershipServiceInterface $members
    ) {}

    /** GET /api/v1/orgs/{id}/members */
    public function index(Request $request, string $id): JsonResponse
    {
        $members = $this->members->listMembers(
            $id,
            $request->only(['status']),
            (int) $request->get('per_page', 25)
        );

        return response()->json($members);
    }

    /** POST /api/v1/orgs/{id}/members/invite */
    public function invite(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'email'   => ['required', 'string', 'email'],
            'role_id' => ['required', 'string', 'size:26'],
            'level'   => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $membership = $this->members->invite(
            $id,
            $request->email,
            $request->role_id,
            $request->level,
            $request->user()->id
        );

        return response()->json([
            'message'    => 'Invitation created.',
            'membership' => $membership,
        ], 201);
    }

    /** POST /api/v1/orgs/{id}/members/accept/{membershipId} */
    public function accept(Request $request, string $id, string $membershipId): JsonResponse
    {
        $membership = $this->members->acceptInvite($membershipId, $request->user()->id);

        return response()->json(['message' => 'Invite accepted.', 'membership' => $membership]);
    }

    /** POST /api/v1/orgs/{id}/members/decline/{membershipId} */
    public function decline(Request $request, string $id, string $membershipId): JsonResponse
    {
        $this->members->declineInvite($membershipId, $request->user()->id);

        return response()->json(['message' => 'Invite declined.']);
    }

    /** DELETE /api/v1/orgs/{id}/members/{userId} */
    public function remove(Request $request, string $id, string $userId): JsonResponse
    {
        $this->members->removeMember($id, $userId, $request->user()->id);

        return response()->json(['message' => 'Member removed.']);
    }

    /** PATCH /api/v1/orgs/{id}/members/{membershipId} */
    public function update(Request $request, string $id, string $membershipId): JsonResponse
    {
        $request->validate([
            'org_role_id' => ['sometimes', 'string', 'size:26'],
            'level'       => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        $membership = $this->members->updateMember(
            $membershipId,
            $request->only(['org_role_id', 'level']),
            $request->user()->id
        );

        return response()->json(['message' => 'Member updated.', 'membership' => $membership]);
    }
}
