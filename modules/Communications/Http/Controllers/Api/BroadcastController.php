<?php

namespace Modules\Communications\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Communications\Services\BroadcastService;

class BroadcastController extends Controller
{
    public function __construct(protected BroadcastService $broadcasts) {}

    /** GET /api/v1/communications/broadcasts */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->broadcasts->listForOwner($request->user()->actor_id, (int) $request->get('per_page', 25)));
    }

    /** POST /api/v1/communications/broadcasts */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['name' => ['required', 'string']]);
        $broadcast = $this->broadcasts->create($request->user()->actor_id, $request->all());
        return response()->json(['message' => 'Broadcast list created.', 'broadcast' => $broadcast], 201);
    }

    /** POST /api/v1/communications/broadcasts/{id}/messages */
    public function send(Request $request, string $id): JsonResponse
    {
        $message = $this->broadcasts->send($id, $request->user()->actor_id, $request->all());
        return response()->json(['message' => 'Broadcast sent.', 'data' => $message], 201);
    }

    /** GET /api/v1/communications/broadcasts/{id}/messages */
    public function messages(Request $request, string $id): JsonResponse
    {
        return response()->json($this->broadcasts->getMessages($id, (int) $request->get('per_page', 50)));
    }

    /** POST /api/v1/communications/broadcasts/{id}/recipients */
    public function addRecipient(Request $request, string $id): JsonResponse
    {
        $request->validate(['actor_id' => ['required', 'string', 'size:26']]);
        $this->broadcasts->addRecipient($id, $request->actor_id);
        return response()->json(['message' => 'Recipient added.'], 201);
    }

    /** DELETE /api/v1/communications/broadcasts/{id}/recipients/{actorId} */
    public function removeRecipient(string $id, string $actorId): JsonResponse
    {
        $this->broadcasts->removeRecipient($id, $actorId);
        return response()->json(['message' => 'Recipient removed.']);
    }
}
