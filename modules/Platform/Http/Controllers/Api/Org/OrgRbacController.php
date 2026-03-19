<?php

namespace Modules\Platform\Http\Controllers\Api\Org;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\OrgRbacServiceInterface;

class OrgRbacController extends Controller
{
    public function __construct(
        protected OrgRbacServiceInterface $rbac
    ) {}

    /** POST /api/v1/orgs/{id}/delegations */
    public function delegate(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'child_org_id'    => ['required', 'string', 'size:26'],
            'role_id'         => ['required', 'string', 'size:26'],
            'permission_ids'  => ['required', 'array'],
            'permission_ids.*'=> ['string', 'size:26'],
        ]);

        $delegation = $this->rbac->delegateRole(
            $id,
            $request->child_org_id,
            $request->role_id,
            $request->permission_ids,
            $request->user()->id
        );

        return response()->json(['message' => 'Role delegated.', 'delegation' => $delegation], 201);
    }

    /** DELETE /api/v1/orgs/{id}/delegations/{delegationId} */
    public function revokeDelegate(Request $request, string $id, string $delegationId): JsonResponse
    {
        $this->rbac->revokeDelegate($delegationId, $request->user()->id);

        return response()->json(['message' => 'Delegation revoked.']);
    }

    /** POST /api/v1/orgs/{id}/permission-requests */
    public function requestPermission(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'role_id'       => ['required', 'string', 'size:26'],
            'permission_id' => ['required', 'string', 'size:26'],
            'reason'        => ['required', 'string', 'min:10'],
        ]);

        $req = $this->rbac->requestPermission(
            $id,
            $request->role_id,
            $request->permission_id,
            $request->reason,
            $request->user()->id
        );

        return response()->json(['message' => 'Permission request submitted.', 'request' => $req], 201);
    }

    /** POST /api/v1/orgs/{id}/permission-requests/{requestId}/approve */
    public function approvePermissionRequest(Request $request, string $id, string $requestId): JsonResponse
    {
        $req = $this->rbac->approvePermissionRequest($requestId, $request->user()->id);

        return response()->json(['message' => 'Permission request approved.', 'request' => $req]);
    }

    /** POST /api/v1/orgs/{id}/permission-requests/{requestId}/deny */
    public function denyPermissionRequest(Request $request, string $id, string $requestId): JsonResponse
    {
        $req = $this->rbac->denyPermissionRequest($requestId, $request->user()->id);

        return response()->json(['message' => 'Permission request denied.', 'request' => $req]);
    }

    /** POST /api/v1/orgs/{id}/scope-requests */
    public function requestScope(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'membership_id'  => ['required', 'string', 'size:26'],
            'scope_type'     => ['required', 'string', 'in:tree_wide,specific_branches'],
            'target_org_ids'=> ['nullable', 'array'],
            'reason'        => ['required', 'string', 'min:10'],
        ]);

        $req = $this->rbac->requestScope(
            $request->membership_id,
            $request->scope_type,
            $request->target_org_ids,
            $request->reason
        );

        return response()->json(['message' => 'Scope request submitted.', 'request' => $req], 201);
    }

    /** POST /api/v1/orgs/{id}/scope-requests/{requestId}/approve */
    public function approveScope(Request $request, string $id, string $requestId): JsonResponse
    {
        $grant = $this->rbac->approveScope($requestId, $request->user()->id);

        return response()->json(['message' => 'Scope approved.', 'grant' => $grant]);
    }

    /** POST /api/v1/orgs/{id}/scope-requests/{requestId}/deny */
    public function denyScope(Request $request, string $id, string $requestId): JsonResponse
    {
        $this->rbac->denyScope($requestId, $request->user()->id);

        return response()->json(['message' => 'Scope request denied.']);
    }
}
