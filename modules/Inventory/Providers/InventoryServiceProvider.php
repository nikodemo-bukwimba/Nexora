<?php

namespace Modules\Inventory\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Contracts\Services\InventoryServiceInterface;
use Modules\Inventory\Contracts\Services\StockAlertServiceInterface;
use Modules\Inventory\Contracts\Services\WarehouseServiceInterface;
use Modules\Inventory\Services\InventoryService;
use Modules\Inventory\Services\StockAlertService;
use Modules\Inventory\Services\WarehouseService;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/inventory.php', 'inventory');

        $this->app->bind(StockAlertServiceInterface::class, StockAlertService::class);
        $this->app->bind(WarehouseServiceInterface::class,  WarehouseService::class);
        $this->app->bind(InventoryServiceInterface::class,  InventoryService::class);
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
