<?php

namespace Modules\Notifications\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Notifications\Services\NotificationService;
use Modules\Notifications\Services\WorkflowService;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/notifications.php', 'notifications');

        $this->app->singleton(NotificationService::class);
        $this->app->singleton(WorkflowService::class);
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
