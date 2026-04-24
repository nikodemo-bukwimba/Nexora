<?php

namespace Modules\Platform\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Platform\Contracts\Services\OrganizationServiceInterface;
use Modules\Platform\Models\Actor;
use Modules\Platform\Models\ActorType;
use Modules\Platform\Models\ActorTypeAssignment;
use Modules\Platform\Models\OrgInvitation;
use Modules\Platform\Models\OrgMembership;
use Modules\Platform\Models\OrgPermissionDefinition;
use Modules\Platform\Models\OrgPermissionRequest;
use Modules\Platform\Models\OrgRole;
use Modules\Platform\Models\OrgRoleDelegation;
use Modules\Platform\Models\Organization;
use Symfony\Component\Uid\Ulid;

class OrganizationService implements OrganizationServiceInterface
{
    // ── Organizations ──────────────────────────────────────────

    public function createRootOrg(array $data, string $userId): Organization
    {
        return DB::connection('platform')->transaction(function () use ($data, $userId) {

            // 1. Create Actor for the org
            $actor = Actor::create([
                'display_name' => $data['name'],
                'status' => 'active',
            ]);

            // 2. Assign 'organization' actor type
            $orgType = ActorType::where('name', 'organization')->firstOrFail();

            $assignedByActorId = \Modules\Platform\Models\User::find($userId)?->actor_id;

            ActorTypeAssignment::insertOrIgnore([
                'actor_id' => $actor->id,
                'actor_type_id' => $orgType->id,
                'assigned_at' => now(),
                'assigned_by' => $assignedByActorId,  // actor_id not user_id
            ]);

            // 3. Create the organization (root — no parent)
            $id = (string) new Ulid();
            $path = $this->ulidToLtreeLabel($id);

            $org = Organization::create([
                'id' => $id,
                'actor_id' => $actor->id,
                'parent_id' => null,
                'root_org_id' => null,   // self — set after save via update
                'path' => $path,
                'depth' => 0,
                'name' => $data['name'],
                'slug' => $this->generateSlug($data['name']),
                'type' => 'root',
                'status' => 'pending_approval',
                'settings' => $data['settings'] ?? null,
            ]);

            // Self-reference for root
            $org->update(['root_org_id' => $org->id]);

            // 4. Create a default owner role for this org tree
            $ownerRole = OrgRole::create([
                'root_org_id' => $org->id,
                'name' => 'Owner',
                'source' => 'custom',
                'is_system' => true,
            ]);

            // 5. Add creator as member with Owner role at level 100
            OrgMembership::create([
                'user_id' => $userId,
                'org_id' => $org->id,
                'org_role_id' => $ownerRole->id,
                'level' => 100,
                'invited_by' => $userId,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            return $org->fresh(['actor']);
        });
    }

    public function createBranch(array $data, string $parentOrgId, string $userId): Organization
    {
        return DB::connection('platform')->transaction(function () use ($data, $parentOrgId, $userId) {

            $parent = Organization::findOrFail($parentOrgId);

            if ($parent->status !== 'active') {
                throw new \RuntimeException('Cannot create a branch under an inactive organization.');
            }

            // 1. Create Actor
            $actor = Actor::create([
                'display_name' => $data['name'],
                'status' => 'active',
            ]);

            $orgType = ActorType::where('name', 'organization')->firstOrFail();
            $assignedByActorId = \Modules\Platform\Models\User::find($userId)?->actor_id;

            ActorTypeAssignment::insertOrIgnore([
                'actor_id' => $actor->id,
                'actor_type_id' => $orgType->id,
                'assigned_at' => now(),
                'assigned_by' => $assignedByActorId,
            ]);

            // 2. Build ltree path
            $id = (string) new Ulid();
            $path = $parent->path . '.' . $this->ulidToLtreeLabel($id);

            $org = Organization::create([
                'id' => $id,
                'actor_id' => $actor->id,
                'parent_id' => $parent->id,
                'root_org_id' => $parent->root_org_id ?? $parent->id,
                'path' => $path,
                'depth' => $parent->depth + 1,
                'name' => $data['name'],
                'slug' => $this->generateSlug($data['name']),
                'type' => 'branch',
                'status' => 'active',  // branches are active by default
                'settings' => $data['settings'] ?? null,
            ]);

            return $org->fresh(['actor', 'parent']);
        });
    }

    public function getOrg(string $id): Organization
    {
        return Organization::with(['actor', 'parent', 'memberships'])->findOrFail($id);
    }

    public function getOrgTree(string $rootOrgId): Collection
    {
        return Organization::whereRaw(
            "path <@ (SELECT path FROM organizations WHERE id = ?)",
            [$rootOrgId]
        )->with('actor')
        ->withCount('memberships')
        ->orderBy('depth')
        ->orderBy('name')
        ->get();
    }

    public function updateOrg(string $id, array $data, string $userId): Organization
    {
        $org = Organization::findOrFail($id);

        $allowed = array_intersect_key($data, array_flip(['name', 'settings']));

        if (isset($allowed['name'])) {
            $org->actor->update(['display_name' => $allowed['name']]);
        }

        $org->update($allowed);

        return $org->fresh(['actor']);
    }

    // ── Org Roles ──────────────────────────────────────────────

    public function createRole(string $rootOrgId, array $data): OrgRole
    {
        $org = Organization::findOrFail($rootOrgId);

        if ($org->type !== 'root') {
            throw new \RuntimeException('Roles can only be defined at root org level.');
        }

        return OrgRole::create([
            'root_org_id' => $rootOrgId,
            'name' => $data['name'],
            'source' => $data['default_role_id'] ?? null ? 'adopted' : 'custom',
            'default_role_id' => $data['default_role_id'] ?? null,
            'is_system' => false,
        ]);
    }

    public function listRoles(string $rootOrgId): Collection
    {
        return OrgRole::where('root_org_id', $rootOrgId)
            ->with('permissions')
            ->get();
    }

    public function assignPermissionsToRole(string $roleId, array $permissionIds): OrgRole
    {
        $role = OrgRole::findOrFail($roleId);

        // Validate all permission IDs exist
        $valid = OrgPermissionDefinition::whereIn('id', $permissionIds)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        // Sync permissions
        DB::connection('platform')->table('org_role_permissions')
            ->where('org_role_id', $roleId)
            ->delete();

        $inserts = array_map(fn($pid) => [
            'org_role_id' => $roleId,
            'org_permission_def_id' => $pid,
        ], $valid);

        if ($inserts) {
            DB::connection('platform')->table('org_role_permissions')->insert($inserts);
        }

        return $role->fresh(['permissions']);
    }

    // ── Memberships ────────────────────────────────────────────

    public function inviteMember(string $orgId, string $email, string $roleId, int $level, string $invitedBy): OrgInvitation
    {
        $org = Organization::findOrFail($orgId);
        $role = OrgRole::findOrFail($roleId);

        if ($level < 0 || $level > 100) {
            throw new \InvalidArgumentException('Level must be between 0 and 100.');
        }

        // Cancel any existing pending invite for this email at this org
        OrgInvitation::where('org_id', $orgId)
            ->where('email', $email)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        return OrgInvitation::create([
            'org_id' => $orgId,
            'org_role_id' => $roleId,
            'level' => $level,
            'email' => $email,
            'token' => \Illuminate\Support\Str::random(64),
            'invited_by' => $invitedBy,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function acceptInvitation(string $token, string $userId): OrgMembership
    {
        $invitation = OrgInvitation::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            $invitation->update(['status' => 'expired']);
            throw new \RuntimeException('Invitation has expired.');
        }

        return DB::connection('platform')->transaction(function () use ($invitation, $userId) {

            $membership = OrgMembership::create([
                'user_id' => $userId,
                'org_id' => $invitation->org_id,
                'org_role_id' => $invitation->org_role_id,
                'level' => $invitation->level,
                'invited_by' => $invitation->invited_by,
                'status' => 'active',
                'joined_at' => now(),
            ]);
            // After: $membership = OrgMembership::create([...]);
            $this->eventBus->fire(new \Modules\Platform\Events\MemberActivated($membership), $membership->user_id);

            $invitation->update(['status' => 'accepted']);

            return $membership->fresh(['organization', 'role']);
        });
    }

    public function removeMember(string $orgId, string $userId, string $removedBy): void
    {
        OrgMembership::where('org_id', $orgId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function listMembers(string $orgId, int $perPage): LengthAwarePaginator
    {
        return OrgMembership::where('org_id', $orgId)
            ->with(['user.actor', 'orgRole'])
            ->paginate($perPage);
    }

    public function updateMember(string $orgId, string $userId, array $data, string $updatedBy): OrgMembership
    {
        $membership = OrgMembership::where('org_id', $orgId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $allowed = array_intersect_key($data, array_flip(['org_role_id', 'level', 'status']));

        if (isset($allowed['level']) && ($allowed['level'] < 0 || $allowed['level'] > 100)) {
            throw new \InvalidArgumentException('Level must be between 0 and 100.');
        }

        $membership->update($allowed);

        return $membership->fresh(['user.actor', 'orgRole']);
    }

    // ── Delegation ─────────────────────────────────────────────

    public function delegateRole(string $parentOrgId, string $childOrgId, string $roleId, array $permissionIds, string $grantedBy): OrgRoleDelegation
    {
        $parent = Organization::findOrFail($parentOrgId);
        $child = Organization::findOrFail($childOrgId);

        if ($child->parent_id !== $parentOrgId) {
            throw new \RuntimeException('Can only delegate to direct child organizations.');
        }

        // Validate permissions are subset of role's own permissions
        $rolePermissions = DB::connection('platform')
            ->table('org_role_permissions')
            ->where('org_role_id', $roleId)
            ->pluck('org_permission_def_id')
            ->toArray();

        $validPermissions = array_intersect($permissionIds, $rolePermissions);

        return DB::connection('platform')->transaction(function () use ($parentOrgId, $childOrgId, $roleId, $validPermissions, $grantedBy) {

            // Revoke existing delegation for same role+child if exists
            $existing = OrgRoleDelegation::where('parent_org_id', $parentOrgId)
                ->where('child_org_id', $childOrgId)
                ->where('org_role_id', $roleId)
                ->first();

            if ($existing) {
                DB::connection('platform')->table('org_delegation_permissions')
                    ->where('delegation_id', $existing->id)->delete();
                $existing->delete();
            }

            $delegation = OrgRoleDelegation::create([
                'parent_org_id' => $parentOrgId,
                'child_org_id' => $childOrgId,
                'org_role_id' => $roleId,
                'granted_by' => $grantedBy,
                'granted_at' => now(),
                'status' => 'active',
            ]);

            $inserts = array_map(fn($pid) => [
                'delegation_id' => $delegation->id,
                'org_permission_def_id' => $pid,
            ], $validPermissions);

            if ($inserts) {
                DB::connection('platform')->table('org_delegation_permissions')->insert($inserts);
            }

            return $delegation->fresh(['role', 'permissions']);
        });
    }

    public function revokeRoleDelegation(string $delegationId, string $revokedBy): void
    {
        $delegation = OrgRoleDelegation::findOrFail($delegationId);
        DB::connection('platform')->table('org_delegation_permissions')
            ->where('delegation_id', $delegationId)->delete();
        $delegation->update(['status' => 'revoked']);
    }

    public function listDelegations(string $orgId): Collection
    {
        return OrgRoleDelegation::where('parent_org_id', $orgId)
            ->orWhere('child_org_id', $orgId)
            ->with(['role', 'permissions', 'childOrg', 'parentOrg'])
            ->where('status', 'active')
            ->get();
    }

    // ── Permission Requests ────────────────────────────────────

    public function requestPermission(string $orgId, string $roleId, string $permissionId, string $reason, string $requestedBy): OrgPermissionRequest
    {
        $org = Organization::findOrFail($orgId);
        $parent = $org->parent;

        if (!$parent) {
            throw new \RuntimeException('Root organizations cannot request permissions — they define them.');
        }

        return OrgPermissionRequest::create([
            'requesting_org_id' => $orgId,
            'target_org_id' => $parent->id,
            'org_role_id' => $roleId,
            'org_permission_def_id' => $permissionId,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }

    public function approvePermissionRequest(string $requestId, string $reviewedBy): OrgPermissionRequest
    {
        $req = OrgPermissionRequest::where('status', 'pending')->findOrFail($requestId);

        $req->update([
            'status' => 'approved',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
        ]);

        return $req->fresh();
    }

    public function denyPermissionRequest(string $requestId, string $reviewedBy): OrgPermissionRequest
    {
        $req = OrgPermissionRequest::where('status', 'pending')->findOrFail($requestId);

        $req->update([
            'status' => 'denied',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
        ]);

        return $req->fresh();
    }

    public function listPermissionRequests(string $orgId, string $status = 'pending'): Collection
    {
        return OrgPermissionRequest::where('requesting_org_id', $orgId)
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->with(['role', 'permission', 'targetOrg'])
            ->get();
    }

    // ── Helpers ────────────────────────────────────────────────

    private function generateSlug(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function ulidToLtreeLabel(string $ulid): string
    {
        // ltree labels must match [A-Za-z0-9_]+ — ULIDs use uppercase + digits which is valid
        return strtolower(str_replace('-', '_', $ulid));
    }
}
