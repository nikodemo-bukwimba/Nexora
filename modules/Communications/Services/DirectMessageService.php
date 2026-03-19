<?php

namespace Modules\Communications\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Communications\Models\DirectConversation;
use Modules\Communications\Models\DirectMessage;

class DirectMessageService
{
    public function getOrCreateConversation(string $actorA, string $actorB): DirectConversation
    {
        // Always store with lower ULID as initiator for consistent lookup
        [$initiator, $recipient] = $actorA < $actorB
            ? [$actorA, $actorB]
            : [$actorB, $actorA];

        return DirectConversation::firstOrCreate(
            ['initiator_actor_id' => $initiator, 'recipient_actor_id' => $recipient],
            ['status' => 'active', 'retention_days' => 0]
        );
    }

    public function listConversations(string $actorId, int $perPage): LengthAwarePaginator
    {
        return DirectConversation::where('initiator_actor_id', $actorId)
            ->orWhere('recipient_actor_id', $actorId)
            ->where('status', 'active')
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage);
    }

    public function send(string $conversationId, string $senderActorId, array $data): DirectMessage
    {
        return DB::connection('communications')->transaction(function () use ($conversationId, $senderActorId, $data) {

            $message = DirectMessage::create([
                'conversation_id'  => $conversationId,
                'sender_actor_id'  => $senderActorId,
                'content'          => $data['content'] ?? null,   // pre-encrypted by client
                'content_type'     => $data['content_type'] ?? 'text',
                'reply_to_id'      => $data['reply_to_id'] ?? null,
                'forwarded_from_id' => $data['forwarded_from_id'] ?? null,
                'forwarded'        => isset($data['forwarded_from_id']),
                'latitude'         => $data['latitude'] ?? null,
                'longitude'        => $data['longitude'] ?? null,
                'status'           => 'sent',
            ]);

            DirectConversation::where('id', $conversationId)->update([
                'last_message_id' => $message->id,
                'last_message_at' => now(),
            ]);

            return $message->fresh(['attachments', 'reactions']);
        });
    }

    public function getMessages(string $conversationId, int $perPage): LengthAwarePaginator
    {
        return DirectMessage::where('conversation_id', $conversationId)
            ->where('deleted_for_everyone', false)
            ->with(['attachments', 'reactions', 'replyTo'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function markDelivered(string $messageId): void
    {
        DirectMessage::where('id', $messageId)
            ->where('status', 'sent')
            ->update(['status' => 'delivered', 'delivered_at' => now()]);
    }

    public function markRead(string $conversationId, string $actorId): void
    {
        DirectMessage::where('conversation_id', $conversationId)
            ->where('sender_actor_id', '!=', $actorId)
            ->where('status', '!=', 'read')
            ->update(['status' => 'read', 'read_at' => now()]);
    }

    public function deleteForMe(string $messageId, string $actorId): void
    {
        $message = DirectMessage::findOrFail($messageId);
        if ($message->sender_actor_id === $actorId) {
            $message->update(['deleted_for_sender' => true]);
        } else {
            $message->update(['deleted_for_recipient' => true]);
        }
    }

    public function deleteForEveryone(string $messageId, string $senderActorId): void
    {
        $message = DirectMessage::where('sender_actor_id', $senderActorId)->findOrFail($messageId);
        $message->update([
            'deleted_for_everyone' => true,
            'content'              => null,
            'deleted_at'           => now(),
        ]);
    }

    public function react(string $messageId, string $actorId, string $emoji): void
    {
        \Modules\Communications\Models\MessageReaction::updateOrCreate(
            ['message_type' => 'DirectMessage', 'message_id' => $messageId, 'actor_id' => $actorId],
            ['emoji' => $emoji]
        );
    }
}
