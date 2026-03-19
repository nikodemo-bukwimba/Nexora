<?php

namespace Modules\PharmaMarketing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PharmaMarketing\Services\DailyReportService;

class DailyReportController extends Controller
{
    public function __construct(protected DailyReportService $reports) {}

    /** GET /api/v1/pharma/orgs/{orgId}/reports */
    public function index(Request $request, string $orgId): JsonResponse
    {
        return response()->json($this->reports->list($orgId, $request->only(['officer_id', 'status', 'date', 'from', 'to']), (int) $request->get('per_page', 25)));
    }

    /** GET /api/v1/pharma/reports/today */
    public function today(Request $request): JsonResponse
    {
        // Requires org_id query param since officer may belong to multiple orgs
        $orgId = $request->query('org_id');
        if (! $orgId) return response()->json(['message' => 'org_id required.'], 422);

        $report = $this->reports->getOrCreateForToday($orgId, $request->user()->actor_id);
        return response()->json($report);
    }

    /** PATCH /api/v1/pharma/reports/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $report = $this->reports->update($id, $request->user()->actor_id, $request->all());
        return response()->json(['message' => 'Report updated.', 'report' => $report]);
    }

    /** POST /api/v1/pharma/reports/{id}/submit */
    public function submit(Request $request, string $id): JsonResponse
    {
        $report = $this->reports->submit($id, $request->user()->actor_id);
        return response()->json(['message' => 'Report submitted.', 'report' => $report]);
    }

    /** POST /api/v1/pharma/reports/{id}/approve */
    public function approve(Request $request, string $id): JsonResponse
    {
        $report = $this->reports->approve($id, $request->user()->actor_id, $request->notes ?? null);
        return response()->json(['message' => 'Report approved.', 'report' => $report]);
    }

    /** POST /api/v1/pharma/reports/{id}/reject */
    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate(['notes' => ['required', 'string', 'min:10']]);
        $report = $this->reports->reject($id, $request->user()->actor_id, $request->notes);
        return response()->json(['message' => 'Report rejected.', 'report' => $report]);
    }
}
