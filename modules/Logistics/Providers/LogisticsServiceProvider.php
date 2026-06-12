<?php

namespace Modules\Logistics\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Logistics\Services\CostCalculationService;
use Modules\Logistics\Services\CourierService;
use Modules\Logistics\Services\DeliveryRunService;
use Modules\Logistics\Services\FleetService;
use Modules\Logistics\Services\OrderTrackingService;

class LogisticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/logistics.php', 'logistics');

        $this->app->bind(CostCalculationService::class);
        $this->app->bind(FleetService::class);
        $this->app->bind(CourierService::class);
        $this->app->singleton(OrderTrackingService::class);
        $this->app->bind(DeliveryRunService::class);
        $this->app->bind(\Modules\Logistics\Services\DeliveryNotificationService::class);
        $this->app->bind(\Modules\Logistics\Services\DriverLocationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Route::middleware('api')
             ->prefix('api/v1')
             ->name('api.')
             ->group(__DIR__ . '/../Routes/api.php');
    }
}
