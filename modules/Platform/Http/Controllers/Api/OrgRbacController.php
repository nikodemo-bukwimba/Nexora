<?php

namespace Modules\Platform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\OrgRbacServiceInterface;

class OrgRbacController extends Controller
{
    public function __construct(
        protected OrgRbacServiceInterface $rbac
    ) {}

    /** GET /api/v1/orgs/{orgId}/roles */
    public function listRoles(string $orgId): JsonResponse
    {
        return response()->json($this->rbac->listRoles($orgId));
    }

    /** POST /api/v1/orgs/{orgId}/roles */
    public function createRole(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'name'            => ['required', 'string', 'max:100'],
            'source'          => ['sometimes', 'string', 'in:adopted,custom'],
            'default_role_id' => ['nullable', 'string', 'size:26'],
            'permissions'     => ['sometimes', 'array'],
            'permissions.*'   => ['string', 'exists:platform.org_permission_definitions,name'],
        ]);

        $role = $this->rbac->createRole($orgId, $request->only(['name', 'source', 'default_role_id']));

        if ($request->filled('permissions')) {
            $role = $this->rbac->assignPermissionsToRole($role->id, $request->permissions);
        }

        return response()->json(['message' => 'Role created.', 'role' => $role], 201);
    }

    /** POST /api/v1/orgs/{orgId}/roles/{roleId}/permissions */
    public function assignPermissions(Request $request, string $orgId, string $roleId): JsonResponse
    {
        $request->validate([
            'permissions'   => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:platform.org_permission_definitions,name'],
        ]);

        $role = $this->rbac->assignPermissionsToRole($roleId, $request->permissions);

        return response()->json(['message' => 'Permissions assigned.', 'role' => $role]);
    }

    /** POST /api/v1/orgs/{orgId}/delegations */
    public function delegate(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'child_org_id'  => ['required', 'string', 'size:26', 'exists:platform.organizations,id'],
            'org_role_id'   => ['required', 'string', 'size:26', 'exists:platform.org_roles,id'],
            'permissions'   => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:platform.org_permission_definitions,name'],
        ]);

        $delegation = $this->rbac->delegateRole(
            $orgId,
            $request->child_org_id,
            $request->org_role_id,
            $request->permissions,
            $request->user()->id
        );

        return response()->json(['message' => 'Role delegated.', 'delegation' => $delegation], 201);
    }

    /** DELETE /api/v1/orgs/{orgId}/delegations/{delegationId} */
    public function revokeDelegate(string $orgId, string $delegationId): JsonResponse
    {
        $this->rbac->revokeDelegate($delegationId);

        return response()->json(['message' => 'Delegation revoked.']);
    }

    /** GET /api/v1/orgs/{orgId}/permission-requests */
    public function listRequests(Request $request, string $orgId): JsonResponse
    {
        $status = $request->get('status', 'pending');

        return response()->json($this->rbac->listPermissionRequests($orgId, $status));
    }

    /** POST /api/v1/orgs/{orgId}/permission-requests */
    public function requestPermission(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'org_role_id'     => ['required', 'string', 'size:26', 'exists:platform.org_roles,id'],
            'permission_name' => ['required', 'string', 'exists:platform.org_permission_definitions,name'],
            'reason'          => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $pr = $this->rbac->requestPermission(
            $orgId,
            $request->org_role_id,
            $request->permission_name,
            $request->reason
        );

        return response()->json(['message' => 'Permission request submitted.', 'request' => $pr], 201);
    }

    /** POST /api/v1/orgs/{orgId}/permission-requests/{requestId}/approve */
    public function approveRequest(Request $request, string $orgId, string $requestId): JsonResponse
    {
        $pr = $this->rbac->approvePermissionRequest($requestId, $request->user()->id);

        return response()->json(['message' => 'Request approved.', 'request' => $pr]);
    }

    /** POST /api/v1/orgs/{orgId}/permission-requests/{requestId}/deny */
    public function denyRequest(Request $request, string $orgId, string $requestId): JsonResponse
    {
        $pr = $this->rbac->denyPermissionRequest($requestId, $request->user()->id);

        return response()->json(['message' => 'Request denied.', 'request' => $pr]);
    }

    /** POST /api/v1/orgs/{orgId}/members/{membershipId}/scope */
    public function grantScope(Request $request, string $orgId, string $membershipId): JsonResponse
    {
        $request->validate([
            'scope_type'   => ['required', 'string', 'in:tree_wide,specific_branches'],
            'branch_ids'   => ['required_if:scope_type,specific_branches', 'array'],
            'branch_ids.*' => ['string', 'size:26', 'exists:platform.organizations,id'],
        ]);

        $grant = $this->rbac->grantScope(
            $membershipId,
            $request->scope_type,
            $request->get('branch_ids', []),
            $request->user()->id
        );

        return response()->json(['message' => 'Scope granted.', 'grant' => $grant], 201);
    }

    /** DELETE /api/v1/orgs/{orgId}/scope-grants/{grantId} */
    public function revokeScope(string $orgId, string $grantId): JsonResponse
    {
        $this->rbac->revokeScope($grantId);

        return response()->json(['message' => 'Scope revoked.']);
    }
}
