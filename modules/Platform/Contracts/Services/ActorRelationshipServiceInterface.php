<?php

namespace Modules\Platform\Contracts\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Platform\Models\ActorRelationship;

interface ActorRelationshipServiceInterface
{
    /**
     * Record or update a relationship between two actors.
     * Called automatically when actors interact via any module.
     */
    public function record(
        string $actorId,
        string $relatedActorId,
        string $relationshipType,
        string $sourceModule,
        string $sourceEvent,
        string $direction = 'bilateral',
        ?array $metadata = null
    ): ActorRelationship;

    /**
     * Get all relationships for an actor.
     */
    public function getRelationships(string $actorId, int $perPage): LengthAwarePaginator;

    /**
     * Get relationships of a specific type for an actor.
     */
    public function getByType(string $actorId, string $type): Collection;

    /**
     * Confirm a pending bilateral relationship.
     */
    public function confirm(string $relationshipId): ActorRelationship;

    /**
     * Revoke a relationship.
     */
    public function revoke(string $relationshipId): void;
}
