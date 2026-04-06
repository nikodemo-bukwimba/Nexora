<?php

use Illuminate\Support\Facades\Route;
use Modules\Platform\Http\Controllers\Api\Org\OrgDelegationController;
use Modules\Platform\Http\Controllers\Api\Org\OrgMembershipController;
use Modules\Platform\Http\Controllers\Api\Org\OrgPermissionRequestController;
use Modules\Platform\Http\Controllers\Api\Org\OrgRoleController;
use Modules\Platform\Http\Controllers\Api\Org\OrganizationController;

/*
|--------------------------------------------------------------------------
| Platform Org API Routes
| Prefix  : /api/v1
| Auth    : auth:sanctum on all routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // ── Organizations ──────────────────────────────────────
    Route::post('orgs',                    [OrganizationController::class, 'createRoot'])->name('orgs.create');
    Route::get('orgs/{id}',                [OrganizationController::class, 'show'])->name('orgs.show');
    Route::get('orgs/{id}/tree',           [OrganizationController::class, 'tree'])->name('orgs.tree');
    Route::patch('orgs/{id}',              [OrganizationController::class, 'update'])->name('orgs.update');
    Route::post('orgs/{orgId}/branches',   [OrganizationController::class, 'createBranch'])->name('orgs.branches.create');

    // ── Org Roles ──────────────────────────────────────────
    Route::get('orgs/{orgId}/roles',                              [OrgRoleController::class, 'index'])->name('orgs.roles.index');
    Route::post('orgs/{orgId}/roles',                             [OrgRoleController::class, 'store'])->name('orgs.roles.store');
    Route::delete('orgs/{orgId}/roles/{roleId}',                  [OrgRoleController::class, 'destroy'])->name('orgs.roles.destroy');
    Route::post('orgs/{orgId}/roles/{roleId}/permissions',        [OrgRoleController::class, 'assignPermissions'])->name('orgs.roles.permissions');

    // ── Org Permission Definitions (catalog) ───────────────
    Route::get('orgs/{orgId}/permissions',                        [OrgRoleController::class, 'permissions'])->name('orgs.permissions.index');

    // ── Members ────────────────────────────────────────────
    Route::get('orgs/{orgId}/members',                [OrgMembershipController::class, 'index'])->name('orgs.members.index');
    Route::post('orgs/{orgId}/members/invite',        [OrgMembershipController::class, 'invite'])->name('orgs.members.invite');
    Route::post('orgs/{orgId}/members/assign',        [OrgMembershipController::class, 'assign'])->name('orgs.members.assign');  // ← NEW
    Route::delete('orgs/{orgId}/members/{userId}',    [OrgMembershipController::class, 'remove'])->name('orgs.members.remove');
    Route::patch('orgs/{orgId}/members/{userId}',     [OrgMembershipController::class, 'update'])->name('orgs.members.update');
    Route::post('orgs/invitations/{token}/accept',    [OrgMembershipController::class, 'accept'])->name('orgs.invitations.accept');

    // ── Delegations ────────────────────────────────────────
    Route::get('orgs/{orgId}/delegations',                      [OrgDelegationController::class, 'index'])->name('orgs.delegations.index');
    Route::post('orgs/{orgId}/delegations',                     [OrgDelegationController::class, 'store'])->name('orgs.delegations.store');
    Route::delete('orgs/{orgId}/delegations/{delegationId}',    [OrgDelegationController::class, 'revoke'])->name('orgs.delegations.revoke');

    // ── Permission Requests ────────────────────────────────
    Route::get('orgs/{orgId}/permission-requests',                          [OrgPermissionRequestController::class, 'index'])->name('orgs.permission-requests.index');
    Route::post('orgs/{orgId}/permission-requests',                         [OrgPermissionRequestController::class, 'store'])->name('orgs.permission-requests.store');
    Route::post('orgs/{orgId}/permission-requests/{requestId}/approve',     [OrgPermissionRequestController::class, 'approve'])->name('orgs.permission-requests.approve');
    Route::post('orgs/{orgId}/permission-requests/{requestId}/deny',        [OrgPermissionRequestController::class, 'deny'])->name('orgs.permission-requests.deny');
});