<?php

namespace Modules\Platform\Http\Controllers\Api\Org;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\OrganizationServiceInterface;
use Modules\Platform\Http\Requests\Org\DelegateRoleRequest;

class OrgDelegationController extends Controller
{
    public function __construct(
        protected OrganizationServiceInterface $orgService
    ) {}

    /** GET /api/v1/orgs/{orgId}/delegations */
    public function index(string $orgId): JsonResponse
    {
        $delegations = $this->orgService->listDelegations($orgId);
        return response()->json($delegations);
    }

    /** POST /api/v1/orgs/{orgId}/delegations */
    public function store(DelegateRoleRequest $request, string $orgId): JsonResponse
    {
        $delegation = $this->orgService->delegateRole(
            $orgId,
            $request->child_org_id,
            $request->org_role_id,
            $request->permission_ids ?? [],
            $request->user()->id
        );

        return response()->json(['message' => 'Role delegated.', 'delegation' => $delegation], 201);
    }

    /** DELETE /api/v1/orgs/{orgId}/delegations/{delegationId} */
    public function revoke(Request $request, string $orgId, string $delegationId): JsonResponse
    {
        $this->orgService->revokeRoleDelegation($delegationId, $request->user()->id);
        return response()->json(['message' => 'Delegation revoked.']);
    }
}
