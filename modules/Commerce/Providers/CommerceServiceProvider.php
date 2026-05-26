<?php

namespace Modules\Commerce\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Commerce\Services\BasketService;
use Modules\Commerce\Services\OrderService;
use Modules\Commerce\Services\ProductService;

class CommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/commerce.php', 'commerce');

        $this->app->bind(ProductService::class);
        $this->app->bind(BasketService::class);
        $this->app->bind(OrderService::class);
        $this->app->bind(
            \Modules\Commerce\Services\BranchPricingService::class,
            \Modules\Commerce\Services\BranchPricingService::class
        );
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
