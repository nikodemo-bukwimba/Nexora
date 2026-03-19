<?php

namespace Modules\Communications\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Communications\Services\DirectMessageService;

class DirectMessageController extends Controller
{
    public function __construct(protected DirectMessageService $dms) {}

    /** GET /api/v1/communications/conversations */
    public function conversations(Request $request): JsonResponse
    {
        return response()->json($this->dms->listConversations($request->user()->actor_id, (int) $request->get('per_page', 25)));
    }

    /** POST /api/v1/communications/conversations */
    public function startConversation(Request $request): JsonResponse
    {
        $request->validate(['recipient_actor_id' => ['required', 'string', 'size:26']]);
        $conversation = $this->dms->getOrCreateConversation($request->user()->actor_id, $request->recipient_actor_id);
        return response()->json($conversation, 201);
    }

    /** GET /api/v1/communications/conversations/{id}/messages */
    public function messages(Request $request, string $conversationId): JsonResponse
    {
        return response()->json($this->dms->getMessages($conversationId, (int) $request->get('per_page', 50)));
    }

    /** POST /api/v1/communications/conversations/{id}/messages */
    public function send(Request $request, string $conversationId): JsonResponse
    {
        $request->validate(['content_type' => ['required', 'string']]);
        $message = $this->dms->send($conversationId, $request->user()->actor_id, $request->all());
        return response()->json(['message' => 'Message sent.', 'data' => $message], 201);
    }

    /** POST /api/v1/communications/conversations/{id}/read */
    public function markRead(Request $request, string $conversationId): JsonResponse
    {
        $this->dms->markRead($conversationId, $request->user()->actor_id);
        return response()->json(['message' => 'Marked as read.']);
    }

    /** DELETE /api/v1/communications/messages/{id}/me */
    public function deleteForMe(Request $request, string $messageId): JsonResponse
    {
        $this->dms->deleteForMe($messageId, $request->user()->actor_id);
        return response()->json(['message' => 'Deleted for you.']);
    }

    /** DELETE /api/v1/communications/messages/{id}/everyone */
    public function deleteForEveryone(Request $request, string $messageId): JsonResponse
    {
        $this->dms->deleteForEveryone($messageId, $request->user()->actor_id);
        return response()->json(['message' => 'Deleted for everyone.']);
    }

    /** POST /api/v1/communications/messages/{id}/react */
    public function react(Request $request, string $messageId): JsonResponse
    {
        $request->validate(['emoji' => ['required', 'string']]);
        $this->dms->react($messageId, $request->user()->actor_id, $request->emoji);
        return response()->json(['message' => 'Reaction added.']);
    }
}
