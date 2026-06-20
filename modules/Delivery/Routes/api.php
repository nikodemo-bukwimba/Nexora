<?php

// === FILE: Modules/Delivery/Routes/api.php

use Illuminate\Support\Facades\Route;
use Modules\Delivery\Http\Controllers\Api\DeliveryController;
use Modules\Delivery\Http\Middleware\EnsureOrgScope;

/*
|--------------------------------------------------------------------------
| Delivery Module API Routes
| Prefix  : /api/v1  (set in DeliveryServiceProvider)
|--------------------------------------------------------------------------
*/

// ── Public tracking — NO auth required ─────────────────────────────────
Route::get('track/{trackingNumber}', [DeliveryController::class, 'publicTrack'])
    ->name('delivery.track.public');

// ── Org-scoped delivery endpoints — auth + org-scope required ──────────
Route::middleware(['auth:sanctum', EnsureOrgScope::class])->group(function () {

    Route::prefix('orgs/{orgId}/deliveries')
        ->name('delivery.')
        ->group(function () {

            Route::get('/',    [DeliveryController::class, 'index'])->name('index');
            Route::post('/',   [DeliveryController::class, 'store'])->name('store');
            Route::get('/{id}', [DeliveryController::class, 'show'])->name('show');
            Route::patch('/{id}', [DeliveryController::class, 'update'])->name('update');
            Route::delete('/{id}', [DeliveryController::class, 'destroy'])->name('destroy');

            // Status transition (admin/staff)
            Route::patch('/{id}/transition',
                [DeliveryController::class, 'transition'])->name('transition');

            // Customer delivery confirmation with invoice + signed document
            Route::post('/{id}/confirm',
                [DeliveryController::class, 'confirm'])->name('confirm');

            // Live GPS update (driver app)
            Route::patch('/{id}/location',
                [DeliveryController::class, 'updateLocation'])->name('location');

            // Parcel / waybill image uploads
            Route::post('/{id}/images',
                [DeliveryController::class, 'uploadImages'])->name('images');
        });
});