<?php

namespace Modules\Communications\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Resolves actor_id → display_name by querying the platform schema.
 *
 * Used by DirectMessageService and GroupService so every API response
 * contains human-readable names instead of raw ULIDs.
 *
 * Design:
 *   - One query per request context (results cached in $_resolved).
 *   - Cross-schema: communications DB writes, platform DB reads (same server).
 *   - Safe: missing actor IDs fall back to a short truncated ID, never null.
 */
trait ActorNameResolver
{
    /** @var array<string,string> actor_id → display_name, local request cache */
    private array $_resolved = [];

    /**
     * Resolve a single actor_id to its display_name.
     * Hits the per-request cache first, then the platform.actors table.
     */
    protected function resolveName(string $actorId): string
    {
        if (empty($actorId)) return 'Unknown';
        if (isset($this->_resolved[$actorId])) return $this->_resolved[$actorId];

        $actor = DB::connection('platform')
            ->table('actors')
            ->where('id', $actorId)
            ->value('display_name');

        $name = $actor ?? $this->_shortId($actorId);
        $this->_resolved[$actorId] = $name;
        return $name;
    }

    /**
     * Resolve multiple actor IDs in a single query and populate the cache.
     * Always call this before resolving individual IDs in a loop.
     *
     * @param string[] $actorIds
     */
    protected function resolveNames(array $actorIds): void
    {
        $needed = array_values(array_filter(
            array_unique($actorIds),
            fn($id) => !empty($id) && !isset($this->_resolved[$id])
        ));

        if (empty($needed)) return;

        $rows = DB::connection('platform')
            ->table('actors')
            ->whereIn('id', $needed)
            ->pluck('display_name', 'id');

        foreach ($needed as $id) {
            $this->_resolved[$id] = $rows[$id] ?? $this->_shortId($id);
        }
    }

    /** Returns a short readable fallback when no display_name is found. */
    private function _shortId(string $id): string
    {
        return strlen($id) > 8
            ? substr($id, 0, 4) . '…' . substr($id, -4)
            : $id;
    }
}