<?php

use Illuminate\Support\Facades\Route;
use Modules\Platform\Http\Controllers\Api\AuthController;
use Modules\Platform\Http\Controllers\Api\ActivityLogController;
use Modules\Platform\Http\Controllers\Api\MediaController;


Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
    });

    // ── General media upload ───────────────────────────────────
    Route::post('media/upload', [MediaController::class, 'upload'])->name('media.upload');
});

Route::middleware('auth:sanctum')->prefix('platform')->name('platform.')->group(function () {
    Route::get('orgs/{orgId}/activity-logs',  [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('activity-logs/{id}',          [ActivityLogController::class, 'show'])->name('activity-logs.show');
});