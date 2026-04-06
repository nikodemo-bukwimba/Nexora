<?php

namespace Modules\Platform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Models\ActivityLog;

class ActivityLogController extends Controller
{
    /** GET /api/v1/platform/orgs/{orgId}/activity-logs */
    public function index(Request $request, string $orgId): JsonResponse
    {
        $logs = ActivityLog::forOrg($orgId)
            ->when($request->entity_type, fn($q, $v) => $q->where('entity_type', $v))
            ->when($request->actor_id,    fn($q, $v) => $q->where('actor_id', $v))
            ->when($request->action,      fn($q, $v) => $q->where('action', $v))
            ->orderByDesc('occurred_at')
            ->paginate((int) $request->get('per_page', 50));

        return response()->json($logs);
    }

    /** GET /api/v1/platform/activity-logs/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json(ActivityLog::findOrFail($id));
    }
}