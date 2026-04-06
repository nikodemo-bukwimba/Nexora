<?php

namespace Modules\Platform\Http\Controllers\Api\Org;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Platform\Contracts\Services\OrganizationServiceInterface;
use Modules\Platform\Http\Requests\Org\CreateOrgRoleRequest;
use Modules\Platform\Http\Requests\Org\AssignRolePermissionsRequest;
use Modules\Platform\Models\OrgInvitation;
use Modules\Platform\Models\OrgMembership;
use Modules\Platform\Models\OrgPermissionDefinition;
use Modules\Platform\Models\OrgRole;
use Modules\Platform\Models\OrgRoleDelegation;

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

    /** DELETE /api/v1/orgs/{orgId}/roles/{roleId} */
    public function destroy(Request $request, string $orgId, string $roleId): JsonResponse
    {
        $role = OrgRole::where('root_org_id', $orgId)
            ->where('id', $roleId)
            ->firstOrFail();

        if ($role->is_system) {
            return response()->json([
                'message' => 'System roles cannot be deleted.',
            ], 422);
        }

        // Check if any active members are using this role
        $memberCount = OrgMembership::where('org_role_id', $roleId)
            ->where('status', 'active')
            ->count();

        if ($memberCount > 0) {
            return response()->json([
                'message' => "Cannot delete role: {$memberCount} active member(s) are assigned to it. Reassign them first.",
            ], 422);
        }

        // Clean up all FK references in a transaction, then delete
        DB::connection('platform')->transaction(function () use ($role, $roleId) {
            // 1. Remove delegation permissions (child of delegations)
            $delegationIds = OrgRoleDelegation::where('org_role_id', $roleId)->pluck('id');
            if ($delegationIds->isNotEmpty()) {
                DB::connection('platform')
                    ->table('org_delegation_permissions')
                    ->whereIn('delegation_id', $delegationIds)
                    ->delete();
            }

            // 2. Remove delegations referencing this role
            OrgRoleDelegation::where('org_role_id', $roleId)->delete();

            // 3. Remove role-permission assignments
            $role->permissions()->detach();

            // 4. Cancel any pending invitations for this role
            OrgInvitation::where('org_role_id', $roleId)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            // 5. Remove inactive/invited memberships still referencing this role
            OrgMembership::where('org_role_id', $roleId)
                ->whereIn('status', ['invited', 'suspended'])
                ->delete();

            // 6. Delete the role
            $role->delete();
        });

        return response()->json(['message' => 'Role deleted.']);
    }

    /** POST /api/v1/orgs/{orgId}/roles/{roleId}/permissions */
    public function assignPermissions(AssignRolePermissionsRequest $request, string $orgId, string $roleId): JsonResponse
    {
        $role = $this->orgService->assignPermissionsToRole($roleId, $request->permission_ids);
        return response()->json(['message' => 'Permissions assigned.', 'role' => $role]);
    }

    /**
     * GET /api/v1/orgs/{orgId}/permissions
     *
     * Returns the full catalog of available org permission definitions.
     */
    public function permissions(string $orgId): JsonResponse
    {
        $permissions = OrgPermissionDefinition::where('is_active', true)
            ->orderBy('group_name')
            ->orderBy('name')
            ->get(['id', 'name', 'group_name', 'description']);

        return response()->json(['data' => $permissions]);
    }
}