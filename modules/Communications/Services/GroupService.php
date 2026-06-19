<?php

namespace Modules\Communications\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Communications\Models\Group;
use Modules\Communications\Models\GroupMessage;
use Modules\Communications\Models\GroupParticipant;
use Modules\Communications\Models\MessageReaction;
use Modules\Communications\Models\MessageReceipt;
use Modules\Communications\Traits\ActorNameResolver;
use Modules\Communications\Models\DirectMessage;

class GroupService
{
    use ActorNameResolver;

    public function create(string $createdBy, array $data): array
    {
        $group = DB::connection('communications')->transaction(function () use ($createdBy, $data) {

            $group = Group::create([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'created_by'  => $createdBy,
                'org_id'      => $data['org_id'] ?? null,
                'type'        => $data['type'] ?? 'group',
                'status'      => 'active',
            ]);

            GroupParticipant::create([
                'group_id' => $group->id,
                'actor_id' => $createdBy,
                'role'     => 'super_admin',
                'status'   => 'active',
                'added_by' => $createdBy,
            ]);

        foreach ($data['participant_ids'] ?? [] as $actorId) {
            if ($actorId === $createdBy) continue;
            GroupParticipant::updateOrCreate(  // ← was: create
                ['group_id' => $group->id, 'actor_id' => $actorId],
                ['role' => 'member', 'status' => 'active', 'added_by' => $createdBy]
            );
        }

            $this->sendSystemMessage($group->id, $createdBy, 'group_created', 'Group created');

            return $group->fresh(['participants']);
        });

        return $this->formatGroup($group);
    }

    public function get(string $id): array
    {
        $group = Group::with(['participants'])->findOrFail($id);
        return $this->formatGroup($group);
    }

    public function addParticipant(string $groupId, string $actorId, string $addedBy): GroupParticipant
    {
        $group = Group::findOrFail($groupId);

        if (! $group->isAdmin($addedBy)) {
            throw new \RuntimeException('Only admins can add participants.');
        }

        $participant = GroupParticipant::updateOrCreate(
            ['group_id' => $groupId, 'actor_id' => $actorId],
            ['status' => 'active', 'added_by' => $addedBy, 'role' => 'member']
        );

        $this->sendSystemMessage($groupId, $actorId, 'member_joined', null);

        return $participant;
    }

    public function removeParticipant(string $groupId, string $actorId, string $removedBy): void
    {
        $group = Group::findOrFail($groupId);

        if ($actorId !== $removedBy && ! $group->isAdmin($removedBy)) {
            throw new \RuntimeException('Only admins can remove participants.');
        }

        GroupParticipant::where('group_id', $groupId)
            ->where('actor_id', $actorId)
            ->update(['status' => 'left', 'left_at' => now()]);

        $event = $actorId === $removedBy ? 'member_left' : 'member_removed';
        $this->sendSystemMessage($groupId, $actorId, $event, null);
    }

    public function promoteToAdmin(string $groupId, string $actorId, string $promotedBy): GroupParticipant
    {
        $group = Group::findOrFail($groupId);
        if (! $group->isAdmin($promotedBy)) {
            throw new \RuntimeException('Only admins can promote members.');
        }

        $participant = GroupParticipant::where('group_id', $groupId)
            ->where('actor_id', $actorId)
            ->firstOrFail();

        $participant->update(['role' => 'admin']);
        return $participant->fresh();
    }

public function send(string $groupId, string $senderActorId, array $data): array
{
    $group = Group::findOrFail($groupId);

    if (! $group->hasParticipant($senderActorId)) {
        throw new \RuntimeException('You are not a participant of this group.');
    }

    if ($group->only_admins_can_message && ! $group->isAdmin($senderActorId)) {
        throw new \RuntimeException('Only admins can send messages in this group.');
    }

    $message = DB::connection('communications')->transaction(function () use ($groupId, $senderActorId, $data, $group) {

        $message = GroupMessage::create([
            'group_id'          => $groupId,
            'sender_actor_id'   => $senderActorId,
            'content'           => $data['content'] ?? null,
            'content_type'      => $data['content_type'] ?? 'text',
            'reply_to_id'       => $data['reply_to_id'] ?? null,
            'forwarded_from_id' => $data['forwarded_from_id'] ?? null,
            'forwarded'         => isset($data['forwarded_from_id']),
            'latitude'          => $data['latitude'] ?? null,
            'longitude'         => $data['longitude'] ?? null,
        ]);

        // Link any pre-uploaded attachments to this message
        if (!empty($data['attachment_ids'])) {
            \Modules\Communications\Models\MessageAttachment::whereIn('id', $data['attachment_ids'])
                ->whereNull('message_id')  // safety: only unclaimed attachments
                ->update([
                    'message_type' => 'GroupMessage',
                    'message_id'   => $message->id,
                ]);
        }

        $group->update([
            'last_message_id' => $message->id,
            'last_message_at' => now(),
        ]);

        return $message->fresh(['attachments', 'reactions']);
    });

    return $this->formatMessage($message);
}

    public function getMessages(string $groupId, int $perPage): LengthAwarePaginator
    {
        $paginator = GroupMessage::where('group_id', $groupId)
            ->where('deleted_for_everyone', false)
            ->with(['attachments', 'reactions', 'replyTo', 'receipts'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Resolve all sender names in one query
        $senderIds = $paginator->pluck('sender_actor_id')->unique()->values()->all();
        $this->resolveNames($senderIds);

        $paginator->through(fn($msg) => $this->formatMessage($msg));

        return $paginator;
    }

    public function markRead(string $groupId, string $actorId): void
    {
        $unread = GroupMessage::where('group_id', $groupId)
            ->where('sender_actor_id', '!=', $actorId)
            ->pluck('id');

        foreach ($unread as $messageId) {
            MessageReceipt::updateOrCreate(
                ['message_type' => 'GroupMessage', 'message_id' => $messageId, 'actor_id' => $actorId],
                ['read_at' => now(), 'delivered_at' => now()]
            );
        }
    }

    public function react(string $messageId, string $actorId, string $emoji): void
    {
        MessageReaction::updateOrCreate(
            ['message_type' => 'GroupMessage', 'message_id' => $messageId, 'actor_id' => $actorId],
            ['emoji' => $emoji]
        );
    }

    public function deleteForEveryone(string $messageId, string $actorId): void
    {
        $message = GroupMessage::findOrFail($messageId);
        $group   = Group::find($message->group_id);

        if ($message->sender_actor_id !== $actorId && ! $group->isAdmin($actorId)) {
            throw new \RuntimeException('Only the sender or admins can delete messages for everyone.');
        }

        $message->update([
            'deleted_for_everyone' => true,
            'content'              => null,
            'deleted_at'           => now(),
        ]);
    }

    public function listForActor(string $actorId): \Illuminate\Support\Collection
    {
        $groups = Group::whereHas('participants', fn($q) =>
            $q->where('actor_id', $actorId)->where('status', 'active')
        )->with(['participants'])->orderBy('last_message_at', 'desc')->get();

        // Resolve all participant actor IDs in one query
        $allActorIds = $groups->flatMap(fn($g) =>
            $g->participants->pluck('actor_id')
        )->unique()->values()->all();

        $this->resolveNames($allActorIds);

        return $groups->map(fn($g) => $this->formatGroup($g));
    }

    public function close(string $groupId, string $actorId): void
    {
        $group = Group::findOrFail($groupId);
        if (! $group->isAdmin($actorId)) {
            throw new \RuntimeException('Only admins can close the group.');
        }
        $group->update(['status' => 'closed', 'closed_by' => $actorId, 'closed_at' => now()]);
    }

    public function reopen(string $groupId, string $actorId): void
    {
        $group = Group::findOrFail($groupId);
        if (! $group->isAdmin($actorId)) {
            throw new \RuntimeException('Only admins can reopen the group.');
        }
        $group->update(['status' => 'active', 'closed_by' => null, 'closed_at' => null]);
    }

    public function editMessage(string $messageId, string $actorId, string $content): array
    {
        $message = GroupMessage::findOrFail($messageId);
        $group   = Group::find($message->group_id);
        if ($message->sender_actor_id !== $actorId && ! $group->isAdmin($actorId)) {
            throw new \RuntimeException('Only the sender or admins can edit messages.');
        }
        $message->update(['content' => $content, 'edited_at' => now()]);
        return $this->formatMessage($message->fresh());
    }

    public function togglePin(string $messageId, string $actorId): bool
    {
        $message = GroupMessage::findOrFail($messageId);
        $group   = Group::find($message->group_id);
        if (! $group->isAdmin($actorId)) {
            throw new \RuntimeException('Only admins can pin messages.');
        }
        $pinned = !$message->is_pinned;
        $message->update(['is_pinned' => $pinned]);
        return $pinned;
    }

    public function toggleStar(string $messageId, string $actorId): bool
    {
        $message = GroupMessage::findOrFail($messageId);
        $starred = !$message->is_starred;
        $message->update(['is_starred' => $starred]);
        return $starred;
    }

    public function getReadReceipts(string $messageId): \Illuminate\Support\Collection
    {
        return MessageReceipt::where('message_type', 'GroupMessage')
            ->where('message_id', $messageId)
            ->whereNotNull('read_at')
            ->get()
            ->map(fn($r) => [
                'actor_id' => $r->actor_id,
                'name'     => $this->resolveName($r->actor_id),
                'read_at'  => $r->read_at,
            ]);
    }

    private function sendSystemMessage(string $groupId, string $actorId, string $event, ?string $content): void
    {
        GroupMessage::create([
            'group_id'          => $groupId,
            'sender_actor_id'   => $actorId,
            'content'           => $content,
            'content_type'      => 'text',
            'is_system_message' => true,
            'system_event'      => $event,
        ]);
    }

    // ── Formatters ────────────────────────────────────────────

    /**
     * Format a Group with resolved participant names.
     * Participants array shape matches what _normalizeGroup() in the
     * Flutter datasource expects:
     *   { actor_id, id (= actor_id), name, role, online_status }
     */
    private function formatGroup(Group $group): array
    {
        $participants = $group->participants->map(fn($p) => [
            'id'           => $p->actor_id,   // Flutter reads p['id'] as the actor_id
            'actor_id'     => $p->actor_id,
            'name'         => $this->resolveName($p->actor_id),
            'role'         => $p->role,
            'online_status' => 'offline',      // enriched by PresenceService if needed
            'last_seen_at' => null,
        ])->values()->all();

        return [
            'id'               => $group->id,
            'type'             => 'group',
            'name'             => $group->name,
            'title'            => $group->name,
            'description'      => $group->description,
            'avatar_url'       => $group->avatar_url,
            'created_by'       => $group->created_by,
            'status'           => $group->status,
            'participants'     => $participants,
            'last_message_id'  => $group->last_message_id,
            'last_message_at'  => $group->last_message_at?->toIso8601String(),
            'unread_count'     => 0,
            'created_at'       => $group->created_at?->toIso8601String(),
            'updated_at'       => $group->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Format a GroupMessage with resolved sender_name AND reply fields.
     */
    private function formatMessage(GroupMessage $msg): array
    {
        // ── Reply fields ───────────────────────────────────────
        $replyToSenderName = null;
        $replyToContent    = null;
        if ($msg->reply_to_id) {
            $replyTo = GroupMessage::find($msg->reply_to_id);
            if ($replyTo) {
                $replyToSenderName = $this->resolveName($replyTo->sender_actor_id);
                // For image/document messages pass the attachment URL so
                // Flutter can render a thumbnail in the reply preview.
                if (in_array($replyTo->content_type, ['image', 'document'])) {
                    $attachment = $replyTo->attachments()->first();
                    $replyToContent = $attachment?->file_url ?? $replyTo->content;
                } else {
                    $replyToContent = $replyTo->content;
                }
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
                'name'      => $a->file_name,
                'size'     => $a->size ?? 0,
            ])->values()->all();
        }
 
        return [
            'id'                   => $msg->id,
            'group_id'             => $msg->group_id,
            'conversation_id'      => $msg->group_id,   // alias for Flutter
            'sender_actor_id'      => $msg->sender_actor_id,
            'sender_name'          => $this->resolveName($msg->sender_actor_id),
            'content'              => $msg->content,
            'content_type'         => $msg->content_type,
            // Reply fields
            'reply_to_id'          => $msg->reply_to_id,
            'reply_to_sender_name' => $replyToSenderName,
            'reply_to_content'     => $replyToContent,
            // Forwarded
            'forwarded_from_id'    => $msg->forwarded_from_id,
            'forwarded'            => (bool) $msg->forwarded,
            // State
            'deleted_for_everyone' => (bool) $msg->deleted_for_everyone,
            'is_system_message'    => (bool) ($msg->is_system_message ?? false),
            'system_event'         => $msg->system_event,
            'is_pinned'            => (bool) ($msg->is_pinned ?? false),
            'is_starred'           => (bool) ($msg->is_starred ?? false),
            'is_edited'            => isset($msg->edited_at),
            'edited_at'            => $msg->edited_at?->toIso8601String(),
            'created_at'           => $msg->created_at?->toIso8601String(),
            // Attachments
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