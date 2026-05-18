<?php

namespace Modules\Communications\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Communications\Models\DirectConversation;
use Modules\Communications\Models\DirectMessage;
use Modules\Communications\Traits\ActorNameResolver;

class DirectMessageService
{
    use ActorNameResolver;

    public function getOrCreateConversation(string $actorA, string $actorB): array
    {
        // Always store with lower ULID as initiator for consistent lookup
        [$initiator, $recipient] = $actorA < $actorB
            ? [$actorA, $actorB]
            : [$actorB, $actorA];

        $conv = DirectConversation::firstOrCreate(
            ['initiator_actor_id' => $initiator, 'recipient_actor_id' => $recipient],
            ['status' => 'active', 'retention_days' => 0]
        );

        return $this->formatConversation($conv);
    }

    public function listConversations(string $actorId, int $perPage): LengthAwarePaginator
    {
        $paginator = DirectConversation::where('initiator_actor_id', $actorId)
            ->orWhere('recipient_actor_id', $actorId)
            ->where('status', 'active')
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage);

        // Resolve all actor names in one query
        $actorIds = $paginator->flatMap(fn($c) => [
            $c->initiator_actor_id,
            $c->recipient_actor_id,
        ])->unique()->values()->all();

        $this->resolveNames($actorIds);

        // Replace items with enriched format
        $paginator->through(fn($conv) => $this->formatConversation($conv));

        return $paginator;
    }

    public function getConversation(string $conversationId): array
    {
        $conv = DirectConversation::findOrFail($conversationId);
        return $this->formatConversation($conv);
    }

    public function send(string $conversationId, string $senderActorId, array $data): array
    {
        $message = DB::connection('communications')->transaction(function () use ($conversationId, $senderActorId, $data) {
            $message = DirectMessage::create([
                'conversation_id'   => $conversationId,
                'sender_actor_id'   => $senderActorId,
                'content'           => $data['content'] ?? null,
                'content_type'      => $data['content_type'] ?? 'text',
                'reply_to_id'       => $data['reply_to_id'] ?? null,
                'forwarded_from_id' => $data['forwarded_from_id'] ?? null,
                'forwarded'         => isset($data['forwarded_from_id']),
                'latitude'          => $data['latitude'] ?? null,
                'longitude'         => $data['longitude'] ?? null,
                'status'            => 'sent',
            ]);

            DirectConversation::where('id', $conversationId)->update([
                'last_message_id' => $message->id,
                'last_message_at' => now(),
            ]);

            return $message->fresh(['attachments', 'reactions']);
        });

        return $this->formatMessage($message);
    }

    public function getMessages(string $conversationId, int $perPage): LengthAwarePaginator
    {
        $paginator = DirectMessage::where('conversation_id', $conversationId)
            ->where('deleted_for_everyone', false)
            ->with(['attachments', 'reactions', 'replyTo'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Resolve all sender names in one query
        $senderIds = $paginator->pluck('sender_actor_id')->unique()->values()->all();
        $this->resolveNames($senderIds);

        $paginator->through(fn($msg) => $this->formatMessage($msg));

        return $paginator;
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

    public function close(string $conversationId, string $actorId): void
    {
        $conv = DirectConversation::findOrFail($conversationId);
        $conv->update(['status' => 'closed', 'closed_by' => $actorId, 'closed_at' => now()]);
    }

    public function reopen(string $conversationId, string $actorId): void
    {
        $conv = DirectConversation::findOrFail($conversationId);
        $conv->update(['status' => 'active', 'closed_by' => null, 'closed_at' => null]);
    }

    public function editMessage(string $messageId, string $actorId, string $content): array
    {
        $message = DirectMessage::where('sender_actor_id', $actorId)->findOrFail($messageId);
        $message->update(['content' => $content, 'edited_at' => now()]);
        return $this->formatMessage($message->fresh());
    }

    public function togglePin(string $messageId, string $actorId): bool
    {
        $message = DirectMessage::findOrFail($messageId);
        $pinned  = !$message->is_pinned;
        $message->update(['is_pinned' => $pinned]);
        return $pinned;
    }

    public function toggleStar(string $messageId, string $actorId): bool
    {
        $message = DirectMessage::findOrFail($messageId);
        $starred = !$message->is_starred;
        $message->update(['is_starred' => $starred]);
        return $starred;
    }

    public function getReadReceipts(string $messageId): \Illuminate\Support\Collection
    {
        return \Modules\Communications\Models\MessageReceipt::where('message_type', 'DirectMessage')
            ->where('message_id', $messageId)
            ->whereNotNull('read_at')
            ->get()
            ->map(function ($r) {
                return [
                    'actor_id' => $r->actor_id,
                    'name'     => $this->resolveName($r->actor_id),
                    'read_at'  => $r->read_at,
                ];
            });
    }

    // ── Formatters ────────────────────────────────────────────

    /**
     * Format a DirectConversation into a flat array with resolved names.
     * This is what the Flutter client receives — it reads:
     *   initiator_actor_id, initiator_name, recipient_actor_id, recipient_name
     */
    private function formatConversation(DirectConversation $conv): array
    {
        return [
            'id'                   => $conv->id,
            'type'                 => 'direct',
            'status'               => $conv->status,
            'initiator_actor_id'   => $conv->initiator_actor_id,
            'initiator_name'       => $this->resolveName($conv->initiator_actor_id),
            'recipient_actor_id'   => $conv->recipient_actor_id,
            'recipient_name'       => $this->resolveName($conv->recipient_actor_id),
            'last_message_id'      => $conv->last_message_id,
            'last_message_at'      => $conv->last_message_at?->toIso8601String(),
            'unread_count'         => 0,   // TODO: per-actor unread tracking
            'created_at'           => $conv->created_at?->toIso8601String(),
            'updated_at'           => $conv->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Format a DirectMessage with resolved sender_name AND reply fields.
     *
     * Flutter MessageModel.fromJson reads:
     *   - sender_actor_id, sender_name
     *   - reply_to_id, reply_to_sender_name, reply_to_content
     *   - attachments[0].url  (for images)
     *   - content_type        (to detect images)
     */
    private function formatMessage(DirectMessage $msg): array
    {
        // ── Reply fields ───────────────────────────────────────
        // Load the replied-to message if this message is a reply.
        // We do this here (not in the query) to avoid an N+1 in the
        // normal case where most messages are NOT replies.
        $replyToSenderName = null;
        $replyToContent    = null;
        if ($msg->reply_to_id) {
            $replyTo = DirectMessage::find($msg->reply_to_id);
            if ($replyTo) {
                $replyToSenderName = $this->resolveName($replyTo->sender_actor_id);
                $replyToContent    = $replyTo->content;
            }
        }
 
        // ── Attachments ────────────────────────────────────────
        $attachments = [];
        if ($msg->relationLoaded('attachments')) {
            $attachments = $msg->attachments->map(fn($a) => [
                'id'       => $a->id,
                'url'      => $a->file_url ?? $a->url,
                'file_url' => $a->file_url ?? $a->url,
                'type'     => $a->type ?? 'image',
                'name'     => $a->original_name ?? '',
                'size'     => $a->size ?? 0,
            ])->values()->all();
        }
 
        return [
            'id'                   => $msg->id,
            'conversation_id'      => $msg->conversation_id,
            'sender_actor_id'      => $msg->sender_actor_id,
            'sender_name'          => $this->resolveName($msg->sender_actor_id),
            'content'              => $msg->content,
            'content_type'         => $msg->content_type,
            // Reply fields — Flutter reads these to populate the reply preview
            'reply_to_id'          => $msg->reply_to_id,
            'reply_to_sender_name' => $replyToSenderName,
            'reply_to_content'     => $replyToContent,
            // Forwarded
            'forwarded_from_id'    => $msg->forwarded_from_id,
            'forwarded'            => (bool) $msg->forwarded,
            // State
            'deleted_for_everyone' => (bool) $msg->deleted_for_everyone,
            'is_pinned'            => (bool) ($msg->is_pinned ?? false),
            'is_starred'           => (bool) ($msg->is_starred ?? false),
            'is_edited'            => isset($msg->edited_at),
            'edited_at'            => $msg->edited_at?->toIso8601String(),
            'status'               => $msg->status,
            'delivered_at'         => $msg->delivered_at?->toIso8601String(),
            'read_at'              => $msg->read_at?->toIso8601String(),
            'created_at'           => $msg->created_at?->toIso8601String(),
            // Attachments — Flutter reads attachments[0].url for images
            'attachments'          => $attachments,
            'reactions'            => $msg->relationLoaded('reactions')
                ? $msg->reactions->map(fn($r) => [
                    'actor_id' => $r->actor_id,
                    'emoji'    => $r->emoji,
                ])->values()->all()
                : [],
        ];
    }
}