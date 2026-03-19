<?php

namespace Modules\Platform\Contracts\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Platform\Models\Organization;
use Modules\Platform\Models\OrgInvitation;
use Modules\Platform\Models\OrgMembership;
use Modules\Platform\Models\OrgPermissionRequest;
use Modules\Platform\Models\OrgRole;
use Modules\Platform\Models\OrgRoleDelegation;

interface OrganizationServiceInterface
{
    // ── Organizations ──────────────────────────────────────────
    public function createRootOrg(array $data, string $userId): Organization;
    public function createBranch(array $data, string $parentOrgId, string $userId): Organization;
    public function getOrg(string $id): Organization;
    public function getOrgTree(string $rootOrgId): Collection;
    public function updateOrg(string $id, array $data, string $userId): Organization;

    // ── Org Roles ──────────────────────────────────────────────
    public function createRole(string $rootOrgId, array $data): OrgRole;
    public function listRoles(string $rootOrgId): Collection;
    public function assignPermissionsToRole(string $roleId, array $permissionIds): OrgRole;

    // ── Memberships ────────────────────────────────────────────
    public function inviteMember(string $orgId, string $email, string $roleId, int $level, string $invitedBy): OrgInvitation;
    public function acceptInvitation(string $token, string $userId): OrgMembership;
    public function removeMember(string $orgId, string $userId, string $removedBy): void;
    public function listMembers(string $orgId, int $perPage): LengthAwarePaginator;
    public function updateMember(string $orgId, string $userId, array $data, string $updatedBy): OrgMembership;

    // ── Delegation ─────────────────────────────────────────────
    public function delegateRole(string $parentOrgId, string $childOrgId, string $roleId, array $permissionIds, string $grantedBy): OrgRoleDelegation;
    public function revokeRoleDelegation(string $delegationId, string $revokedBy): void;
    public function listDelegations(string $orgId): Collection;

    // ── Permission Requests ────────────────────────────────────
    public function requestPermission(string $orgId, string $roleId, string $permissionId, string $reason, string $requestedBy): OrgPermissionRequest;
    public function approvePermissionRequest(string $requestId, string $reviewedBy): OrgPermissionRequest;
    public function denyPermissionRequest(string $requestId, string $reviewedBy): OrgPermissionRequest;
    public function listPermissionRequests(string $orgId, string $status): Collection;
}
