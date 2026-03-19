<?php

namespace Modules\Platform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\ActorRelationshipServiceInterface;

class ActorRelationshipController extends Controller
{
    public function __construct(
        protected ActorRelationshipServiceInterface $relationships
    ) {}

    /**
     * GET /api/v1/actors/{actorId}/relationships
     * List all relationships for an actor.
     */
    public function index(Request $request, string $actorId): JsonResponse
    {
        $relationships = $this->relationships->getRelationships(
            $actorId,
            (int) $request->get('per_page', 25)
        );

        return response()->json($relationships);
    }

    /**
     * GET /api/v1/actors/{actorId}/relationships/{type}
     * Get relationships of a specific type.
     */
    public function byType(string $actorId, string $type): JsonResponse
    {
        $relationships = $this->relationships->getByType($actorId, $type);

        return response()->json($relationships);
    }

    /**
     * POST /api/v1/actors/{actorId}/relationships/{relationshipId}/confirm
     * Confirm a pending bilateral relationship.
     */
    public function confirm(string $actorId, string $relationshipId): JsonResponse
    {
        $relationship = $this->relationships->confirm($relationshipId);

        return response()->json([
            'message'      => 'Relationship confirmed.',
            'relationship' => $relationship,
        ]);
    }

    /**
     * DELETE /api/v1/actors/{actorId}/relationships/{relationshipId}
     * Revoke a relationship.
     */
    public function revoke(string $actorId, string $relationshipId): JsonResponse
    {
        $this->relationships->revoke($relationshipId);

        return response()->json(['message' => 'Relationship revoked.']);
    }
}
