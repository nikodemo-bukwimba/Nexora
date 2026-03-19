<?php

namespace Modules\Communications\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Communications\Services\PresenceService;

class PresenceController extends Controller
{
    public function __construct(protected PresenceService $presence) {}

    /** POST /api/v1/communications/presence/online */
    public function online(Request $request): JsonResponse
    {
        $this->presence->setOnline($request->user()->actor_id);
        return response()->json(['status' => 'online']);
    }

    /** POST /api/v1/communications/presence/offline */
    public function offline(Request $request): JsonResponse
    {
        $this->presence->setOffline($request->user()->actor_id);
        return response()->json(['status' => 'offline']);
    }

    /** GET /api/v1/communications/presence/{actorId} */
    public function show(string $actorId): JsonResponse
    {
        $presence = $this->presence->getPresence($actorId);
        return response()->json($presence ?? ['actor_id' => $actorId, 'is_online' => false, 'last_seen_at' => null]);
    }

    /** POST /api/v1/communications/presence/bulk */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate(['actor_ids' => ['required', 'array', 'max:100']]);
        return response()->json($this->presence->getBulkPresence($request->actor_ids));
    }
}
