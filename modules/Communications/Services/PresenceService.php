<?php

namespace Modules\Communications\Services;

use Modules\Communications\Models\ActorPresence;

class PresenceService
{
    public function setOnline(string $actorId): void
    {
        ActorPresence::updateOrCreate(
            ['actor_id' => $actorId],
            ['is_online' => true, 'last_seen_at' => now(), 'updated_at' => now()]
        );
    }

    public function setOffline(string $actorId): void
    {
        ActorPresence::updateOrCreate(
            ['actor_id' => $actorId],
            ['is_online' => false, 'last_seen_at' => now(), 'updated_at' => now()]
        );
    }

    public function getPresence(string $actorId): ?ActorPresence
    {
        $presence = ActorPresence::find($actorId);
        if (! $presence) return null;
        if ($presence->hide_last_seen) {
            $presence->last_seen_at = null;
        }
        return $presence;
    }

    public function getBulkPresence(array $actorIds): array
    {
        return ActorPresence::whereIn('actor_id', $actorIds)
            ->get()
            ->keyBy('actor_id')
            ->map(fn($p) => [
                'actor_id'     => $p->actor_id,
                'is_online'    => $p->is_online,
                'last_seen_at' => $p->hide_last_seen ? null : $p->last_seen_at,
            ])
            ->values()
            ->toArray();
    }
}
