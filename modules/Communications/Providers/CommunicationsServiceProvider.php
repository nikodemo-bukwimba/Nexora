<?php

namespace Modules\Communications\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Communications\Services\BroadcastService;
use Modules\Communications\Services\CommunityService;
use Modules\Communications\Services\DirectMessageService;
use Modules\Communications\Services\GroupService;
use Modules\Communications\Services\PresenceService;

class CommunicationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/communications.php', 'communications');

        $this->app->bind(DirectMessageService::class);
        $this->app->bind(GroupService::class);
        $this->app->bind(BroadcastService::class);
        $this->app->bind(CommunityService::class);
        $this->app->bind(PresenceService::class);
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
