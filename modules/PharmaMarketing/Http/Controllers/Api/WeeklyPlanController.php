<?php

namespace Modules\PharmaMarketing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PharmaMarketing\Services\WeeklyPlanService;

class WeeklyPlanController extends Controller
{
    public function __construct(protected WeeklyPlanService $plans) {}

    /** GET /api/v1/pharma/orgs/{orgId}/plans */
    public function index(Request $request, string $orgId): JsonResponse
    {
        return response()->json($this->plans->list($orgId, $request->only(['officer_id', 'status', 'week']), (int) $request->get('per_page', 25)));
    }

    /** POST /api/v1/pharma/orgs/{orgId}/plans */
    public function store(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'week_start_date' => ['required', 'date'],
        ]);
        $plan = $this->plans->create($orgId, $request->user()->actor_id, $request->all());
        return response()->json(['message' => 'Plan created.', 'plan' => $plan], 201);
    }

    /** GET /api/v1/pharma/plans/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json($this->plans->get($id));
    }

    /** POST /api/v1/pharma/plans/{id}/items */
    public function addItem(Request $request, string $id): JsonResponse
    {
        $request->validate(['planned_date' => ['required', 'date']]);
        $item = $this->plans->addItem($id, $request->all());
        return response()->json(['message' => 'Item added.', 'item' => $item], 201);
    }

    /** POST /api/v1/pharma/plans/{id}/submit */
    public function submit(Request $request, string $id): JsonResponse
    {
        $plan = $this->plans->submit($id, $request->user()->actor_id);
        return response()->json(['message' => 'Plan submitted for approval.', 'plan' => $plan]);
    }

    /** POST /api/v1/pharma/plans/{id}/approve */
    public function approve(Request $request, string $id): JsonResponse
    {
        $plan = $this->plans->approve($id, $request->user()->actor_id);
        return response()->json(['message' => 'Plan approved.', 'plan' => $plan]);
    }

    /** POST /api/v1/pharma/plans/{id}/reject */
    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'min:10']]);
        $plan = $this->plans->reject($id, $request->user()->actor_id, $request->reason);
        return response()->json(['message' => 'Plan rejected.', 'plan' => $plan]);
    }
}
