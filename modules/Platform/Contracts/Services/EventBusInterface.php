<?php

namespace Modules\Platform\Contracts\Services;

use Modules\Platform\Events\PlatformEvent;

interface EventBusInterface
{
    /**
     * Fire a platform event.
     *
     * The event class itself determines dispatch mode:
     *   - Sync:  event does NOT implement ShouldQueue
     *   - Async: event implements ShouldQueue
     *
     * All events are logged to event_dispatch_log automatically.
     * Modules NEVER call Laravel's event() helper directly.
     */
    public function fire(PlatformEvent $event, ?string $actorId = null): void;

    /**
     * Register an event in the event registry.
     * Called by module ServiceProviders on boot.
     */
    public function register(
        string $eventName,
        string $module,
        string $dispatchMode = 'async',
        ?array $payloadSchema = null
    ): void;
}
