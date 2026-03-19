<?php

namespace Modules\Communications\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Communications\Models\Broadcast;
use Modules\Communications\Models\BroadcastMessage;
use Modules\Communications\Models\BroadcastRecipient;

class BroadcastService
{
    public function create(string $ownerActorId, array $data): Broadcast
    {
        $broadcast = Broadcast::create([
            'name'            => $data['name'],
            'owner_actor_id'  => $ownerActorId,
            'org_id'          => $data['org_id'] ?? null,
            'status'          => 'active',
        ]);

        foreach ($data['recipient_actor_ids'] ?? [] as $actorId) {
            BroadcastRecipient::create([
                'broadcast_id' => $broadcast->id,
                'actor_id'     => $actorId,
                'status'       => 'active',
            ]);
        }

        return $broadcast->fresh(['recipients']);
    }

    public function addRecipient(string $broadcastId, string $actorId): void
    {
        BroadcastRecipient::updateOrCreate(
            ['broadcast_id' => $broadcastId, 'actor_id' => $actorId],
            ['status' => 'active']
        );
    }

    public function removeRecipient(string $broadcastId, string $actorId): void
    {
        BroadcastRecipient::where('broadcast_id', $broadcastId)
            ->where('actor_id', $actorId)
            ->update(['status' => 'removed']);
    }

    public function send(string $broadcastId, string $senderActorId, array $data): BroadcastMessage
    {
        return BroadcastMessage::create([
            'broadcast_id'     => $broadcastId,
            'sender_actor_id'  => $senderActorId,
            'content'          => $data['content'] ?? null,
            'content_type'     => $data['content_type'] ?? 'text',
            'latitude'         => $data['latitude'] ?? null,
            'longitude'        => $data['longitude'] ?? null,
        ]);
    }

    public function getMessages(string $broadcastId, int $perPage): LengthAwarePaginator
    {
        return BroadcastMessage::where('broadcast_id', $broadcastId)
            ->with(['attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function listForOwner(string $ownerActorId, int $perPage): LengthAwarePaginator
    {
        return Broadcast::where('owner_actor_id', $ownerActorId)
            ->where('status', 'active')
            ->with(['recipients'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
