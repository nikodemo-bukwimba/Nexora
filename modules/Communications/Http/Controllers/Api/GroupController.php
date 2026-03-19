<?php

namespace Modules\Communications\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Communications\Services\GroupService;

class GroupController extends Controller
{
    public function __construct(protected GroupService $groups) {}

    /** POST /api/v1/communications/groups */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);
        $group = $this->groups->create($request->user()->actor_id, $request->all());
        return response()->json(['message' => 'Group created.', 'group' => $group], 201);
    }

    /** GET /api/v1/communications/groups/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json($this->groups->get($id));
    }

    /** GET /api/v1/communications/groups/{id}/messages */
    public function messages(Request $request, string $id): JsonResponse
    {
        return response()->json($this->groups->getMessages($id, (int) $request->get('per_page', 50)));
    }

    /** POST /api/v1/communications/groups/{id}/messages */
    public function send(Request $request, string $id): JsonResponse
    {
        $message = $this->groups->send($id, $request->user()->actor_id, $request->all());
        return response()->json(['message' => 'Message sent.', 'data' => $message], 201);
    }

    /** POST /api/v1/communications/groups/{id}/participants */
    public function addParticipant(Request $request, string $id): JsonResponse
    {
        $request->validate(['actor_id' => ['required', 'string', 'size:26']]);
        $participant = $this->groups->addParticipant($id, $request->actor_id, $request->user()->actor_id);
        return response()->json(['message' => 'Participant added.', 'participant' => $participant], 201);
    }

    /** DELETE /api/v1/communications/groups/{id}/participants/{actorId} */
    public function removeParticipant(Request $request, string $id, string $actorId): JsonResponse
    {
        $this->groups->removeParticipant($id, $actorId, $request->user()->actor_id);
        return response()->json(['message' => 'Participant removed.']);
    }

    /** POST /api/v1/communications/groups/{id}/participants/{actorId}/promote */
    public function promote(Request $request, string $id, string $actorId): JsonResponse
    {
        $participant = $this->groups->promoteToAdmin($id, $actorId, $request->user()->actor_id);
        return response()->json(['message' => 'Promoted to admin.', 'participant' => $participant]);
    }

    /** POST /api/v1/communications/groups/{id}/read */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $this->groups->markRead($id, $request->user()->actor_id);
        return response()->json(['message' => 'Marked as read.']);
    }

    /** POST /api/v1/communications/messages/group/{messageId}/react */
    public function react(Request $request, string $messageId): JsonResponse
    {
        $request->validate(['emoji' => ['required', 'string']]);
        $this->groups->react($messageId, $request->user()->actor_id, $request->emoji);
        return response()->json(['message' => 'Reaction added.']);
    }

    /** DELETE /api/v1/communications/messages/group/{messageId}/everyone */
    public function deleteForEveryone(Request $request, string $messageId): JsonResponse
    {
        $this->groups->deleteForEveryone($messageId, $request->user()->actor_id);
        return response()->json(['message' => 'Deleted for everyone.']);
    }
}
