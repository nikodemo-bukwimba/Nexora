<?php

namespace Modules\Platform\Events;

use Modules\Platform\Models\ActorRelationship;

class ActorRelationshipCreated extends PlatformEvent
{
    public function __construct(public readonly ActorRelationship $relationship) {}

    public function moduleName(): string { return 'platform'; }
    public function eventName(): string  { return 'actor.relationship.created'; }

    public function payload(): array
    {
        return [
            'relationship_id'   => $this->relationship->id,
            'actor_id'          => $this->relationship->actor_id,
            'related_actor_id'  => $this->relationship->related_actor_id,
            'relationship_type' => $this->relationship->relationship_type,
            'source_module'     => $this->relationship->source_module,
            'direction'         => $this->relationship->direction,
        ];
    }
}
