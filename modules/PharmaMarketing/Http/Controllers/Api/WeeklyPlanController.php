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

    /** PATCH /api/v1/pharma/plans/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'week_start_date' => ['sometimes', 'date'],
            'objectives'      => ['sometimes', 'nullable', 'string'],
            'notes'           => ['sometimes', 'nullable', 'string'],
        ]);
        $plan = $this->plans->update($id, $request->user()->actor_id, $request->only(['week_start_date', 'objectives', 'notes']));
        return response()->json(['message' => 'Plan updated.', 'plan' => $plan]);
    }

    /** DELETE /api/v1/pharma/plans/{id} */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->plans->delete($id, $request->user()->actor_id);
        return response()->json(['message' => 'Plan deleted.']);
    }

    /** POST /api/v1/pharma/plans/{id}/items */
    public function addItem(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'item_type'   => ['required', 'string', 'in:customer_visit,office_work,training,meeting,promotion_event,other'],
            'planned_date' => ['required', 'date'],
            'customer_id' => ['required_if:item_type,customer_visit', 'nullable', 'string'],
        ]);
        $item = $this->plans->addItem($id, $request->all());
        return response()->json(['message' => 'Item added.', 'item' => $item], 201);
    }

    /** DELETE /api/v1/pharma/plans/{planId}/items/{itemId} */
    public function removeItem(Request $request, string $planId, string $itemId): JsonResponse
    {
        $this->plans->removeItem($planId, $itemId, $request->user()->actor_id);
        return response()->json(['message' => 'Item removed.']);
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