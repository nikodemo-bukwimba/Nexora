<?php

namespace Modules\Communications\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Communications\Services\CommunityService;

class CommunityController extends Controller
{
    public function __construct(protected CommunityService $communities) {}

    /** GET /api/v1/communications/communities */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->communities->listPublic((int) $request->get('per_page', 25)));
    }

    /** POST /api/v1/communications/communities */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);
        $community = $this->communities->create($request->user()->actor_id, $request->all());
        return response()->json(['message' => 'Community created.', 'community' => $community], 201);
    }

    /** GET /api/v1/communications/communities/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json($this->communities->get($id));
    }

    /** POST /api/v1/communications/communities/{id}/groups */
    public function addGroup(Request $request, string $id): JsonResponse
    {
        $request->validate(['group_id' => ['required', 'string', 'size:26']]);
        $cg = $this->communities->addGroup($id, $request->group_id, $request->boolean('is_announcement_channel'));
        return response()->json(['message' => 'Group added to community.', 'community_group' => $cg], 201);
    }

    /** DELETE /api/v1/communications/communities/{id}/groups/{groupId} */
    public function removeGroup(string $id, string $groupId): JsonResponse
    {
        $this->communities->removeGroup($id, $groupId);
        return response()->json(['message' => 'Group removed from community.']);
    }

    /** POST /api/v1/communications/communities/{id}/join */
    public function join(Request $request, string $id): JsonResponse
    {
        $member = $this->communities->addMember($id, $request->user()->actor_id);
        return response()->json(['message' => 'Joined community.', 'member' => $member]);
    }
}
