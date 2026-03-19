<?php

namespace Modules\Platform\Services;

use Illuminate\Support\Facades\DB;
use Modules\Platform\Contracts\Services\OrgRbacServiceInterface;
use Modules\Platform\Models\OrgMembership;
use Modules\Platform\Models\OrgPermissionRequest;
use Modules\Platform\Models\OrgRole;
use Modules\Platform\Models\OrgRoleDelegation;
use Modules\Platform\Models\OrgScopeGrant;
use Modules\Platform\Models\OrgScopeRequest;
use Modules\Platform\Models\Organization;

class OrgRbacService implements OrgRbacServiceInterface
{
    // ── Roles ──────────────────────────────────────────────────

    public function createRole(string $rootOrgId, array $data): OrgRole
    {
        // Verify the org is a root org
        $org = Organization::findOrFail($rootOrgId);
        if ($org->type !== 'root') {
            throw new \RuntimeException('Only root organizations can define roles.');
        }

        return OrgRole::create([
            'root_org_id'    => $rootOrgId,
            'name'           => $data['name'],
            'source'         => $data['default_role_id'] ? 'adopted' : 'custom',
            'default_role_id'=> $data['default_role_id'] ?? null,
            'is_system'      => false,
        ]);
    }

    public function listRoles(string $rootOrgId): array
    {
        return OrgRole::where('root_org_id', $rootOrgId)
            ->with('permissions')
            ->get()
            ->toArray();
    }

    public function assignPermissionsToRole(string $roleId, array $permissionIds): void
    {
        $role = OrgRole::findOrFail($roleId);

        // Permissions must exist in org_permission_definitions
        $role->permissions()->sync($permissionIds);
    }

    // ── Delegation ─────────────────────────────────────────────

    public function delegateRole(
        string $parentOrgId,
        string $childOrgId,
        string $roleId,
        array $permissionIds,
        string $grantedBy
    ): OrgRoleDelegation {
        return DB::connection('platform')->transaction(function () use (
            $parentOrgId, $childOrgId, $roleId, $permissionIds, $grantedBy
        ) {
            // Verify child is direct child of parent
            $child = Organization::findOrFail($childOrgId);
            if ($child->parent_id !== $parentOrgId) {
                throw new \RuntimeException('Can only delegate roles to direct child branches.');
            }

            // Verify all permissions are within parent role's own ceiling
            $parentRole = OrgRole::with('permissions')->findOrFail($roleId);
            $parentPermIds = $parentRole->permissions->pluck('id')->toArray();
            $invalid = array_diff($permissionIds, $parentPermIds);
            if (! empty($invalid)) {
                throw new \RuntimeException('Cannot delegate permissions that the parent role does not have.');
            }

            // Create or update delegation
            $delegation = OrgRoleDelegation::updateOrCreate(
                ['parent_org_id' => $parentOrgId, 'child_org_id' => $childOrgId, 'org_role_id' => $roleId],
                ['granted_by' => $grantedBy, 'granted_at' => now(), 'status' => 'active']
            );

            // Sync the delegated permissions
            $delegation->permissions()->sync($permissionIds);

            return $delegation->load('permissions');
        });
    }

    public function revokeDelegate(string $delegationId, string $revokedBy): void
    {
        OrgRoleDelegation::findOrFail($delegationId)->update(['status' => 'revoked']);
    }

    // ── Permission Requests ────────────────────────────────────

    public function requestPermission(
        string $orgId,
        string $roleId,
        string $permissionId,
        string $reason,
        string $userId
    ): OrgPermissionRequest {
        $org = Organization::findOrFail($orgId);

        if (! $org->parent_id) {
            throw new \RuntimeException('Root orgs cannot request permissions — they define them.');
        }

        return OrgPermissionRequest::create([
            'requesting_org_id'    => $orgId,
            'target_org_id'        => $org->parent_id,
            'org_role_id'          => $roleId,
            'org_permission_def_id'=> $permissionId,
            'reason'               => $reason,
            'status'               => 'pending',
        ]);
    }

    public function approvePermissionRequest(string $requestId, string $reviewedBy): OrgPermissionRequest
    {
        $request = OrgPermissionRequest::findOrFail($requestId);

        if ($request->status !== 'pending') {
            throw new \RuntimeException('Request is no longer pending.');
        }

        // Verify reviewer has the permission being requested
        $reviewerHasPermission = $this->can($reviewedBy, '', $request->target_org_id);

        $request->update([
            'status'      => 'approved',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
        ]);

        // Find or create the delegation and add this permission
        $delegation = OrgRoleDelegation::firstOrCreate(
            [
                'parent_org_id' => $request->target_org_id,
                'child_org_id'  => $request->requesting_org_id,
                'org_role_id'   => $request->org_role_id,
            ],
            ['granted_by' => $reviewedBy, 'granted_at' => now(), 'status' => 'active']
        );

        DB::connection('platform')->table('org_delegation_permissions')->insertOrIgnore([
            'delegation_id'        => $delegation->id,
            'org_permission_def_id'=> $request->org_permission_def_id,
        ]);

        return $request->fresh();
    }

    public function denyPermissionRequest(string $requestId, string $reviewedBy): OrgPermissionRequest
    {
        $request = OrgPermissionRequest::findOrFail($requestId);

        if ($request->status !== 'pending') {
            throw new \RuntimeException('Request is no longer pending.');
        }

        $request->update([
            'status'      => 'denied',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
        ]);

        return $request->fresh();
    }

    // ── Scope Grants ───────────────────────────────────────────

    public function requestScope(
        string $membershipId,
        string $scopeType,
        ?array $targetOrgIds,
        string $reason
    ): OrgScopeRequest {
        if (! in_array($scopeType, ['tree_wide', 'specific_branches'])) {
            throw new \InvalidArgumentException('Invalid scope type. Allowed: tree_wide, specific_branches');
        }

        if ($scopeType === 'specific_branches' && empty($targetOrgIds)) {
            throw new \InvalidArgumentException('specific_branches scope requires target_org_ids.');
        }

        return OrgScopeRequest::create([
            'membership_id'  => $membershipId,
            'requested_scope'=> $scopeType,
            'target_org_ids' => $targetOrgIds,
            'reason'         => $reason,
            'status'         => 'pending',
        ]);
    }

    public function approveScope(string $requestId, string $reviewedBy): OrgScopeGrant
    {
        $request = OrgScopeRequest::with('membership')->findOrFail($requestId);

        if ($request->status !== 'pending') {
            throw new \RuntimeException('Request is no longer pending.');
        }

        // tree_wide scope requires level 100 approver in the org tree
        if ($request->requested_scope === 'tree_wide') {
            $membership    = $request->membership;
            $rootOrgId     = Organization::findOrFail($membership->org_id)->root_org_id;
            $approverIsL100 = OrgMembership::where('user_id', $reviewedBy)
                ->whereHas('organization', fn($q) => $q->where('root_org_id', $rootOrgId)->orWhere('id', $rootOrgId))
                ->where('status', 'active')
                ->where('level', 100)
                ->exists();

            if (! $approverIsL100) {
                throw new \RuntimeException('Tree-wide scope can only be approved by a level 100 member.');
            }
        }

        $request->update([
            'status'      => 'approved',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
        ]);

        $grant = OrgScopeGrant::create([
            'membership_id' => $request->membership_id,
            'scope_type'    => $request->requested_scope,
            'granted_by'    => $reviewedBy,
            'granted_at'    => now(),
            'status'        => 'active',
        ]);

        // Attach specific branches if applicable
        if ($request->requested_scope === 'specific_branches' && $request->target_org_ids) {
            $grant->branches()->attach($request->target_org_ids);
        }

        return $grant->load('branches');
    }

    public function denyScope(string $requestId, string $reviewedBy): void
    {
        OrgScopeRequest::findOrFail($requestId)->update([
            'status'      => 'denied',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
        ]);
    }

    // ── Permission Check ───────────────────────────────────────

    /**
     * Check if a user has a specific permission at a given org node.
     *
     * Resolution:
     * 1. Get user's membership(s) at this org
     * 2. For root org: check org_role_permissions directly
     * 3. For branch: check org_delegation_permissions for the delegation chain
     * 4. Never traverse upward to grant — only delegation grants downward
     */
    public function can(string $userId, string $permissionName, string $orgId): bool
    {
        if (empty($permissionName)) return true;

        $org = Organization::findOrFail($orgId);

        $memberships = OrgMembership::where('user_id', $userId)
            ->where('org_id', $orgId)
            ->where('status', 'active')
            ->get();

        if ($memberships->isEmpty()) return false;

        foreach ($memberships as $membership) {
            if ($org->type === 'root') {
                // Root org — check role permissions directly
                $has = DB::connection('platform')
                    ->table('org_role_permissions as orp')
                    ->join('org_permission_definitions as opd', 'opd.id', '=', 'orp.org_permission_def_id')
                    ->where('orp.org_role_id', $membership->org_role_id)
                    ->where('opd.name', $permissionName)
                    ->exists();

                if ($has) return true;
            } else {
                // Branch — check delegation permissions
                $has = DB::connection('platform')
                    ->table('org_role_delegations as ord')
                    ->join('org_delegation_permissions as odp', 'odp.delegation_id', '=', 'ord.id')
                    ->join('org_permission_definitions as opd', 'opd.id', '=', 'odp.org_permission_def_id')
                    ->where('ord.child_org_id', $orgId)
                    ->where('ord.org_role_id', $membership->org_role_id)
                    ->where('ord.status', 'active')
                    ->where('opd.name', $permissionName)
                    ->exists();

                if ($has) return true;
            }
        }

        return false;
    }
}
