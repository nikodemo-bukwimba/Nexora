<?php

namespace Modules\Platform\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base class for all Nexora platform events.
 *
 * Every module event extends this and implements:
 *   - moduleName(): string   e.g. 'platform', 'finance', 'commerce'
 *   - eventName(): string    e.g. 'org.created', 'order.placed'
 *   - payload(): array       serializable data about what happened
 *
 * Usage:
 *   EventBus::fire(new OrgCreated($org));
 *
 * Rules:
 *   - Modules NEVER call Laravel's event() helper directly
 *   - All events go through EventBus::fire()
 *   - Sync events: do NOT implement ShouldQueue
 *   - Async events: implement ShouldQueue on the event class
 */
abstract class PlatformEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The module that fired this event.
     * e.g. 'platform', 'finance', 'commerce', 'inventory'
     */
    abstract public function moduleName(): string;

    /**
     * The event name in dot notation.
     * e.g. 'org.created', 'org.approved', 'member.invited'
     */
    abstract public function eventName(): string;

    /**
     * Serializable payload describing what happened.
     * Must contain only scalar values and arrays — no Eloquent models.
     */
    abstract public function payload(): array;

    /**
     * Fully qualified event identifier.
     * e.g. 'platform.org.created'
     */
    final public function fullEventName(): string
    {
        return $this->moduleName() . '.' . $this->eventName();
    }
}
