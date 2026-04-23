<?php

namespace Modules\Platform\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Platform\Contracts\Repositories\ActorRepositoryInterface;
use Modules\Platform\Contracts\Repositories\UserRepositoryInterface;
use Modules\Platform\Contracts\Services\ActorRelationshipServiceInterface;
use Modules\Platform\Contracts\Services\AuditLoggerInterface;
use Modules\Platform\Contracts\Services\AuthServiceInterface;
use Modules\Platform\Contracts\Services\EventBusInterface;
use Modules\Platform\Contracts\Services\OrgScopeResolverInterface;
use Modules\Platform\Contracts\Services\OrganizationServiceInterface;
use Modules\Platform\Contracts\Services\PlatformAdminServiceInterface;
use Modules\Platform\Http\Middleware\PlatformAdminMiddleware;
use Modules\Platform\Repositories\ActorRepository;
use Modules\Platform\Repositories\UserRepository;
use Modules\Platform\Services\ActorRelationshipService;
use Modules\Platform\Services\AuditLogger;
use Modules\Platform\Services\AuthService;
use Modules\Platform\Services\EventBus;
use Modules\Platform\Services\OrgScopeResolver;
use Modules\Platform\Services\OrganizationService;
use Modules\Platform\Services\PlatformAdminService;

class PlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/platform.php', 'platform');

        // ── Infrastructure ─────────────────────────────────────
        $this->app->singleton(EventBusInterface::class, EventBus::class);
        $this->app->singleton(AuditLoggerInterface::class, AuditLogger::class);

        // ── Repositories ───────────────────────────────────────
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ActorRepositoryInterface::class, ActorRepository::class);

        // ── Services ───────────────────────────────────────────
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(PlatformAdminServiceInterface::class, PlatformAdminService::class);
        $this->app->bind(OrganizationServiceInterface::class, OrganizationService::class);
        $this->app->bind(ActorRelationshipServiceInterface::class, ActorRelationshipService::class);

        // ── OrgScopeResolver — shared across all modules ───────
        // Singleton per request so the in-memory org cache is reused.
        $this->app->singleton(OrgScopeResolverInterface::class, OrgScopeResolver::class);
    }

    public function boot(): void
    {
        // ── Migrations ─────────────────────────────────────────
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // ── Views (for email templates) ────────────────────────
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'platform');

        // ── Middleware ──────────────────────────────────────────
        $this->app['router']->aliasMiddleware('platform.admin', PlatformAdminMiddleware::class);

        // ── Routes ─────────────────────────────────────────────
        Route::middleware('api')->prefix('api/v1')->name('api.platform.')
            ->group(__DIR__ . '/../Routes/api.php');

        Route::middleware('api')->prefix('api/v1')->name('api.platform.')
            ->group(__DIR__ . '/../Routes/admin_api.php');

        Route::middleware('api')->prefix('api/v1')->name('api.platform.')
            ->group(__DIR__ . '/../Routes/org_api.php');

        Route::middleware('api')->prefix('api/v1')->name('api.platform.')
            ->group(__DIR__ . '/../Routes/actor_api.php');

        // ── Register core events ───────────────────────────────
        $this->registerCoreEvents();
    }

    private function registerCoreEvents(): void
    {
        try {
            $bus = $this->app->make(EventBusInterface::class);
            $events = [
                ['platform.user.registered',              'platform', 'sync'],
                ['platform.org.created',                  'platform', 'sync'],
                ['platform.org.approved',                 'platform', 'sync'],
                ['platform.org.rejected',                 'platform', 'sync'],
                ['platform.org.suspended',                'platform', 'sync'],
                ['platform.member.invited',               'platform', 'sync'],
                ['platform.member.joined',                'platform', 'sync'],
                ['platform.actor.relationship.created',   'platform', 'sync'],
            ];
            foreach ($events as [$name, $module, $mode]) {
                $bus->register($name, $module, $mode);
            }
        } catch (\Throwable) {
            // Silently skip during artisan migrate / before DB is ready
        }
    }
}