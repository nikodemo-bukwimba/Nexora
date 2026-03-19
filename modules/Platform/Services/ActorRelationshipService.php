<?php

namespace Modules\Platform\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Platform\Contracts\Services\ActorRelationshipServiceInterface;
use Modules\Platform\Contracts\Services\AuditLoggerInterface;
use Modules\Platform\Contracts\Services\EventBusInterface;
use Modules\Platform\Events\ActorRelationshipCreated;
use Modules\Platform\Models\ActorRelationship;

class ActorRelationshipService implements ActorRelationshipServiceInterface
{
    public function __construct(
        protected EventBusInterface    $eventBus,
        protected AuditLoggerInterface $audit
    ) {}

    public function record(
        string $actorId,
        string $relatedActorId,
        string $relationshipType,
        string $sourceModule,
        string $sourceEvent,
        string $direction = 'bilateral',
        ?array $metadata = null
    ): ActorRelationship {

        // Upsert — if relationship already exists update metadata and timestamps
        $existing = ActorRelationship::where('actor_id', $actorId)
            ->where('related_actor_id', $relatedActorId)
            ->where('relationship_type', $relationshipType)
            ->where('source_module', $sourceModule)
            ->first();

        if ($existing) {
            $existing->update([
                'metadata'      => $metadata ?? $existing->metadata,
                'source_event'  => $sourceEvent,
            ]);
            return $existing->fresh();
        }

        $relationship = ActorRelationship::create([
            'actor_id'          => $actorId,
            'related_actor_id'  => $relatedActorId,
            'relationship_type' => $relationshipType,
            'source_module'     => $sourceModule,
            'source_event'      => $sourceEvent,
            'direction'         => $direction,
            'status'            => $direction === 'unilateral' ? 'active' : 'pending',
            'metadata'          => $metadata,
            'initiated_at'      => now(),
            'confirmed_at'      => $direction === 'unilateral' ? now() : null,
        ]);

        // Fire event
        $this->eventBus->fire(
            new ActorRelationshipCreated($relationship),
            $actorId
        );

        // Audit
        $this->audit->log(
            module:      'platform',
            action:      'actor.relationship.created',
            subjectType: 'ActorRelationship',
            subjectId:   $relationship->id,
            newValues:   $relationship->toArray(),
            actorId:     $actorId
        );

        return $relationship;
    }

    public function getRelationships(string $actorId, int $perPage): LengthAwarePaginator
    {
        return ActorRelationship::where('actor_id', $actorId)
            ->orWhere('related_actor_id', $actorId)
            ->with(['actor', 'relatedActor'])
            ->orderBy('initiated_at', 'desc')
            ->paginate($perPage);
    }

    public function getByType(string $actorId, string $type): Collection
    {
        return ActorRelationship::where('actor_id', $actorId)
            ->orWhere('related_actor_id', $actorId)
            ->where('relationship_type', $type)
            ->where('status', 'active')
            ->with(['actor', 'relatedActor'])
            ->get();
    }

    public function confirm(string $relationshipId): ActorRelationship
    {
        $relationship = ActorRelationship::where('status', 'pending')
            ->findOrFail($relationshipId);

        $relationship->update([
            'status'       => 'active',
            'confirmed_at' => now(),
        ]);

        return $relationship->fresh();
    }

    public function revoke(string $relationshipId): void
    {
        $relationship = ActorRelationship::findOrFail($relationshipId);
        $relationship->update(['status' => 'revoked']);
    }
}
