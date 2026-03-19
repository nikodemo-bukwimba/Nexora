<?php

namespace Modules\Platform\Http\Controllers\Api\Org;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\OrganizationServiceInterface;
use Modules\Platform\Http\Requests\Org\CreateOrgRoleRequest;
use Modules\Platform\Http\Requests\Org\AssignRolePermissionsRequest;

class OrgRoleController extends Controller
{
    public function __construct(
        protected OrganizationServiceInterface $orgService
    ) {}

    /** GET /api/v1/orgs/{orgId}/roles */
    public function index(string $orgId): JsonResponse
    {
        $roles = $this->orgService->listRoles($orgId);
        return response()->json($roles);
    }

    /** POST /api/v1/orgs/{orgId}/roles */
    public function store(CreateOrgRoleRequest $request, string $orgId): JsonResponse
    {
        $role = $this->orgService->createRole($orgId, $request->validated());
        return response()->json(['message' => 'Role created.', 'role' => $role], 201);
    }

    /** POST /api/v1/orgs/{orgId}/roles/{roleId}/permissions */
    public function assignPermissions(AssignRolePermissionsRequest $request, string $orgId, string $roleId): JsonResponse
    {
        $role = $this->orgService->assignPermissionsToRole($roleId, $request->permission_ids);
        return response()->json(['message' => 'Permissions assigned.', 'role' => $role]);
    }
}
