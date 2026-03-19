<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\Api\NotificationController;
use Modules\Notifications\Http\Controllers\Api\WorkflowController;

Route::middleware('auth:sanctum')->group(function () {

    // ── Notifications ──────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',                         [NotificationController::class, 'index'])->name('index');
        Route::post('{id}/read',                [NotificationController::class, 'markRead'])->name('read');
        Route::post('read-all',                 [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::post('devices',                  [NotificationController::class, 'registerDevice'])->name('devices.register');
        Route::delete('devices',                [NotificationController::class, 'deregisterDevice'])->name('devices.deregister');
        Route::get('preferences',               [NotificationController::class, 'preferences'])->name('preferences.index');
        Route::patch('preferences/{type}',      [NotificationController::class, 'updatePreference'])->name('preferences.update');
    });

    // ── Workflows ──────────────────────────────────────────────
    Route::prefix('workflows')->name('workflows.')->group(function () {
        Route::get('/',                         [WorkflowController::class, 'index'])->name('index');
        Route::post('/',                        [WorkflowController::class, 'store'])->name('store');
        Route::get('{id}',                      [WorkflowController::class, 'show'])->name('show');
        Route::get('{id}/runs',                 [WorkflowController::class, 'runs'])->name('runs');
        Route::get('runs/{runId}',              [WorkflowController::class, 'showRun'])->name('runs.show');
    });
});
