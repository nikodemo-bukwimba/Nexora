<?php

namespace Modules\PharmaMarketing\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\PharmaMarketing\Services\CustomerService;
use Modules\PharmaMarketing\Services\DailyReportService;
use Modules\PharmaMarketing\Services\FieldVisitService;
use Modules\PharmaMarketing\Services\ProductUpdateService;
use Modules\PharmaMarketing\Services\WeeklyPlanService;

class PharmaMarketingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/pharma_marketing.php', 'pharma_marketing');

        $this->app->bind(CustomerService::class);
        $this->app->bind(FieldVisitService::class);
        $this->app->bind(WeeklyPlanService::class);
        $this->app->bind(ProductUpdateService::class);
        $this->app->bind(DailyReportService::class);
        $this->app->bind(\Modules\PharmaMarketing\Services\OfficerService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Route::middleware('api')
             ->prefix('api/v1')
             ->name('api.')
             ->group(__DIR__ . '/../Routes/api.php');
        \Illuminate\Support\Facades\Event::listen(
        \Modules\Platform\Events\MemberActivated::class,
        \Modules\PharmaMarketing\Listeners\CreateOfficerOnMemberActivated::class,
        );
    }
}
