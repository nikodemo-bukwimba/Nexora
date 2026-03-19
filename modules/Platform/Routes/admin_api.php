<?php

use Illuminate\Support\Facades\Route;
use Modules\Platform\Http\Controllers\Api\Admin\AdminOrgController;
use Modules\Platform\Http\Controllers\Api\Admin\AdminUserController;
use Modules\Platform\Http\Controllers\Api\Admin\AuditLogController;
use Modules\Platform\Http\Controllers\Api\Admin\FeatureFlagController;
use Modules\Platform\Http\Controllers\Api\Admin\StaffController;
use Modules\Platform\Http\Controllers\Api\Admin\TierController;
use Modules\Platform\Http\Controllers\Api\EventLogController;

Route::middleware('auth:sanctum')->prefix('admin')->name('admin.')->group(function () {

    // ── Staff ──────────────────────────────────────────────────
    Route::middleware('platform.admin:staff.view')->get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::middleware('platform.admin:staff.assign')->post('staff', [StaffController::class, 'assign'])->name('staff.assign');
    Route::middleware('platform.admin:staff.revoke')->delete('staff/{userId}/{roleName}', [StaffController::class, 'revoke'])->name('staff.revoke');

    // ── Organizations ──────────────────────────────────────────
    Route::middleware('platform.admin:orgs.view')->group(function () {
        Route::get('orgs',      [AdminOrgController::class, 'index'])->name('orgs.index');
        Route::get('orgs/{id}', [AdminOrgController::class, 'show'])->name('orgs.show');
    });
    Route::middleware('platform.admin:orgs.approve')->post('orgs/{id}/approve',     [AdminOrgController::class, 'approve'])->name('orgs.approve');
    Route::middleware('platform.admin:orgs.reject')->post('orgs/{id}/reject',       [AdminOrgController::class, 'reject'])->name('orgs.reject');
    Route::middleware('platform.admin:orgs.suspend')->post('orgs/{id}/suspend',     [AdminOrgController::class, 'suspend'])->name('orgs.suspend');
    Route::middleware('platform.admin:orgs.reactivate')->post('orgs/{id}/reactivate', [AdminOrgController::class, 'reactivate'])->name('orgs.reactivate');

    // ── Users ──────────────────────────────────────────────────
    Route::middleware('platform.admin:users.view')->get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::middleware('platform.admin:users.suspend')->patch('users/{id}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
    Route::middleware('platform.admin:users.tier.assign')->post('users/{id}/tier', [AdminUserController::class, 'assignTier'])->name('users.tier');

    // ── Feature flags ──────────────────────────────────────────
    Route::middleware('platform.admin:flags.view')->get('flags', [FeatureFlagController::class, 'index'])->name('flags.index');
    Route::middleware('platform.admin:flags.toggle')->patch('flags/{key}', [FeatureFlagController::class, 'toggle'])->name('flags.toggle');

    // ── Tiers ──────────────────────────────────────────────────
    Route::middleware('platform.admin:tiers.view')->get('tiers', [TierController::class, 'index'])->name('tiers.index');

    // ── Audit log ──────────────────────────────────────────────
    Route::middleware('platform.admin:audit.view')->get('audit', [AuditLogController::class, 'index'])->name('audit.index');

    // ── Event log ─────────────────────────────────────────────
    Route::middleware('platform.admin:audit.view')->group(function () {
        Route::get('events',          [EventLogController::class, 'index'])->name('events.index');
        Route::get('events/registry', [EventLogController::class, 'registry'])->name('events.registry');
    });
});
