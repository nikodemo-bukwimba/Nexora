<?php

namespace Modules\Finance\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Finance\Contracts\Services\CommissionServiceInterface;
use Modules\Finance\Contracts\Services\CreditServiceInterface;
use Modules\Finance\Contracts\Services\InvoiceServiceInterface;
use Modules\Finance\Contracts\Services\PaymentServiceInterface;
use Modules\Finance\Contracts\Services\PromotionServiceInterface;
use Modules\Finance\Contracts\Services\SubscriptionServiceInterface;
use Modules\Finance\Services\CommissionService;
use Modules\Finance\Services\CreditService;
use Modules\Finance\Services\InvoiceService;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Services\PromotionService;
use Modules\Finance\Services\SubscriptionService;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/finance.php', 'finance');

        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->bind(InvoiceServiceInterface::class,      InvoiceService::class);
        $this->app->bind(PaymentServiceInterface::class,      PaymentService::class);
        $this->app->bind(CreditServiceInterface::class,       CreditService::class);
        $this->app->bind(CommissionServiceInterface::class,   CommissionService::class);
        $this->app->bind(PromotionServiceInterface::class,    PromotionService::class);
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
