<?php
// === FILE: Modules/Delivery/Routes/api.php
 
use Illuminate\Support\Facades\Route;
use Modules\Delivery\Http\Controllers\Api\DeliveryController;
 
/*
|--------------------------------------------------------------------------
| Delivery Module API Routes
| Prefix  : /api/v1  (set in DeliveryServiceProvider)
|--------------------------------------------------------------------------
*/
 
// ── Public tracking — NO auth required ─────────────────────────────────
// Anyone with the tracking number can access this.
Route::get('track/{trackingNumber}', [DeliveryController::class, 'publicTrack'])
    ->name('delivery.track.public');
 
// ── Org-scoped delivery endpoints — auth required ───────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('orgs/{orgId}/deliveries')
        ->name('delivery.')
        ->group(function () {

            Route::get('/', [DeliveryController::class, 'index'])
                ->name('index');

            Route::post('/', [DeliveryController::class, 'store'])
                ->name('store');

            Route::get('/{id}', [DeliveryController::class, 'show'])
                ->name('show');

            Route::patch('/{id}', [DeliveryController::class, 'update'])
                ->name('update');

            Route::delete('/{id}', [DeliveryController::class, 'destroy'])
                ->name('destroy');

            // Status transition
            Route::patch('/{id}/transition',
                [DeliveryController::class, 'transition'])
                ->name('transition');

            // Live GPS update
            Route::patch('/{id}/location',
                [DeliveryController::class, 'updateLocation'])
                ->name('location');

            // Delivery image uploads
            Route::post('/{id}/images',
                [DeliveryController::class, 'uploadImages'])
                ->name('images');
        });
});