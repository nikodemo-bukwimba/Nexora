<?php

namespace Modules\Communications\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Communications\Services\GroupService;
use Modules\Notifications\Services\NotificationService;

class GroupController extends Controller
{
    public function __construct(
        protected GroupService        $groups,
        protected NotificationService $notifications,
    ) {}

    /** POST /api/v1/communications/groups */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate(['name' => ['required', 'string', 'max:255']]);
            $group = $this->groups->create($request->user()->actor_id, $request->all());
            return response()->json(['message' => 'Group created.', 'group' => $group], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
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

        // Notify all other participants in the group
        $group = $this->groups->get($id);
        $senderActorId = $request->user()->actor_id;
        $senderName    = $request->user()->actor->display_name ?? 'Someone';

        $recipientActorIds = collect($group->participants ?? [])
            ->pluck('actor_id')
            ->filter(fn($aid) => $aid !== $senderActorId)
            ->values();

        $this->notifications->sendToMany(
            $recipientActorIds->toArray(),
            'message.received',
            $group->name ?? 'Group message',
            "{$senderName}: " . ($request->input('content') ?? 'Sent a message.'),
            ['ref_type' => 'group', 'ref_id' => $id],
        );

        return response()->json(['message' => 'Message sent.', 'data' => $message], 201);
    }

    /** POST /api/v1/communications/groups/{id}/participants */
    public function addParticipant(Request $request, string $id): JsonResponse
    {
        $request->validate(['actor_id' => ['required', 'string', 'size:26']]);
        $participant = $this->groups->addParticipant($id, $request->actor_id, $request->user()->actor_id);

        // Notify the added participant
        $group = $this->groups->get($id);
        $this->notifications->send(
            actorId: $request->actor_id,
            type:    'group.added',
            title:   'Added to group',
            body:    'You have been added to the group: ' . ($group->name ?? 'a group') . '.',
            refType: 'group',
            refId:   $id,
        );

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

    /** GET /api/v1/communications/groups */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->groups->listForActor($request->user()->actor_id));
    }

    /** POST /api/v1/communications/groups/{id}/close */
    public function close(Request $request, string $id): JsonResponse
    {
        $this->groups->close($id, $request->user()->actor_id);
        return response()->json(['message' => 'Group closed.']);
    }

    /** POST /api/v1/communications/groups/{id}/reopen */
    public function reopen(Request $request, string $id): JsonResponse
    {
        $this->groups->reopen($id, $request->user()->actor_id);
        return response()->json(['message' => 'Group reopened.']);
    }

    /** PATCH /api/v1/communications/messages/group/{messageId} */
    public function editMessage(Request $request, string $messageId): JsonResponse
    {
        $request->validate(['content' => ['required', 'string']]);
        $message = $this->groups->editMessage($messageId, $request->user()->actor_id, $request->content);
        return response()->json(['message' => 'Message updated.', 'data' => $message]);
    }

    /** POST /api/v1/communications/messages/group/{messageId}/pin */
    public function pinMessage(Request $request, string $messageId): JsonResponse
    {
        $result = $this->groups->togglePin($messageId, $request->user()->actor_id);
        return response()->json(['message' => $result ? 'Message pinned.' : 'Message unpinned.']);
    }

    /** POST /api/v1/communications/messages/group/{messageId}/star */
    public function starMessage(Request $request, string $messageId): JsonResponse
    {
        $result = $this->groups->toggleStar($messageId, $request->user()->actor_id);
        return response()->json(['message' => $result ? 'Message starred.' : 'Message unstarred.']);
    }

    /** GET /api/v1/communications/messages/group/{messageId}/receipts */
    public function receipts(Request $request, string $messageId): JsonResponse
    {
        $receipts = $this->groups->getReadReceipts($messageId);
        return response()->json(['receipts' => $receipts]);
    }
}