<?php

namespace Modules\Platform\Contracts\Services;

use Modules\Platform\Models\OrgPermissionRequest;
use Modules\Platform\Models\OrgRole;
use Modules\Platform\Models\OrgRoleDelegation;
use Modules\Platform\Models\OrgScopeGrant;
use Modules\Platform\Models\OrgScopeRequest;

interface OrgRbacServiceInterface
{
    // Roles
    public function createRole(string $rootOrgId, array $data): OrgRole;
    public function listRoles(string $rootOrgId): array;
    public function assignPermissionsToRole(string $roleId, array $permissionIds): void;

    // Delegation
    public function delegateRole(string $parentOrgId, string $childOrgId, string $roleId, array $permissionIds, string $grantedBy): OrgRoleDelegation;
    public function revokeDelegate(string $delegationId, string $revokedBy): void;

    // Permission requests
    public function requestPermission(string $orgId, string $roleId, string $permissionId, string $reason, string $userId): OrgPermissionRequest;
    public function approvePermissionRequest(string $requestId, string $reviewedBy): OrgPermissionRequest;
    public function denyPermissionRequest(string $requestId, string $reviewedBy): OrgPermissionRequest;

    // Scope grants
    public function requestScope(string $membershipId, string $scopeType, ?array $targetOrgIds, string $reason): OrgScopeRequest;
    public function approveScope(string $requestId, string $reviewedBy): OrgScopeGrant;
    public function denyScope(string $requestId, string $reviewedBy): void;

    // Permission check
    public function can(string $userId, string $permissionName, string $orgId): bool;
}
