<?php

namespace Modules\Platform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class EventLogController extends Controller
{
    /**
     * GET /api/v1/admin/events
     * Query the event dispatch log.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DB::connection('platform')
            ->table('event_dispatch_log')
            ->when($request->module,     fn($q) => $q->where('module', $request->module))
            ->when($request->event_name, fn($q) => $q->where('event_name', 'like', "%{$request->event_name}%"))
            ->when($request->status,     fn($q) => $q->where('status', $request->status))
            ->when($request->actor_id,   fn($q) => $q->where('actor_id', $request->actor_id))
            ->when($request->from,       fn($q) => $q->where('fired_at', '>=', $request->from))
            ->when($request->to,         fn($q) => $q->where('fired_at', '<=', $request->to))
            ->orderBy('fired_at', 'desc');

        $perPage = (int) $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }

    /**
     * GET /api/v1/admin/events/registry
     * List all registered event types.
     */
    public function registry(): JsonResponse
    {
        $registry = DB::connection('platform')
            ->table('event_registry')
            ->orderBy('module')
            ->orderBy('name')
            ->get();

        return response()->json($registry);
    }
}
