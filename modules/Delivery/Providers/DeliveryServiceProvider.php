<?php
// === FILE: Modules/Delivery/Providers/DeliveryServiceProvider.php
 
namespace Modules\Delivery\Providers;
 
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
 
class DeliveryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/delivery.php', 'delivery');
    }
 
    public function boot(): void
    {
        // ── Migrations ─────────────────────────────────────────
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
 
        // ── Routes ─────────────────────────────────────────────
        Route::middleware('api')
            ->prefix('api/v1')
            ->name('api.delivery.')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}