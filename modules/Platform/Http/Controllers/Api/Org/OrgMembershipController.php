<?php

namespace Modules\Platform\Http\Controllers\Api\Org;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\OrganizationServiceInterface;
use Modules\Platform\Http\Requests\Org\InviteMemberRequest;
use Modules\Platform\Http\Requests\Org\UpdateMemberRequest;

class OrgMembershipController extends Controller
{
    public function __construct(
        protected OrganizationServiceInterface $orgService
    ) {}

    /** GET /api/v1/orgs/{orgId}/members */
    public function index(Request $request, string $orgId): JsonResponse
    {
        $members = $this->orgService->listMembers($orgId, (int) $request->get('per_page', 25));
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

        return response()->json([
            'message'    => 'Invitation sent.',
            'invitation' => $invitation,
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
}
