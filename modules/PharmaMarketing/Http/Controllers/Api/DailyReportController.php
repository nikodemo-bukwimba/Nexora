<?php

namespace Modules\PharmaMarketing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PharmaMarketing\Models\DailyReport;
use Modules\PharmaMarketing\Services\DailyReportService;
use Modules\Platform\Models\Actor;
use Modules\Platform\Models\User;

class DailyReportController extends Controller
{
    public function __construct(protected DailyReportService $reports) {}

    /** GET /api/v1/pharma/orgs/{orgId}/reports */
    public function index(Request $request, string $orgId): JsonResponse
    {
        $paginator = $this->reports->list(
            $orgId,
            $request->only(['officer_id', 'status', 'date', 'from', 'to']),
            (int) $request->get('per_page', 25)
        );

        $paginator->getCollection()->transform(fn($r) => $this->formatReport($r));

        return response()->json($paginator);
    }

    /** GET /api/v1/pharma/reports/{id} */
    public function show(string $id): JsonResponse
    {
        $report = DailyReport::findOrFail($id);
        return response()->json(['data' => $this->formatReport($report)]);
    }

    /** GET /api/v1/pharma/reports/today */
    public function today(Request $request): JsonResponse
    {
        $orgId = $request->query('org_id');
        if (! $orgId) return response()->json(['message' => 'org_id required.'], 422);

        $report = $this->reports->getOrCreateForToday($orgId, $request->user()->actor_id);
        return response()->json(['data' => $this->formatReport($report)]);
    }

    /** PATCH /api/v1/pharma/reports/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $report = $this->reports->update($id, $request->user()->actor_id, $request->all());
        return response()->json(['message' => 'Report updated.', 'data' => $this->formatReport($report)]);
    }

    /** POST /api/v1/pharma/reports/{id}/submit */
    public function submit(Request $request, string $id): JsonResponse
    {
        $report = $this->reports->submit($id, $request->user()->actor_id);
        return response()->json(['message' => 'Report submitted.', 'data' => $this->formatReport($report)]);
    }

    /** POST /api/v1/pharma/reports/{id}/approve */
    public function approve(Request $request, string $id): JsonResponse
    {
        $report = $this->reports->approve($id, $request->user()->actor_id, $request->notes ?? null);
        return response()->json(['message' => 'Report approved.', 'data' => $this->formatReport($report)]);
    }

    /** POST /api/v1/pharma/reports/{id}/reject */
    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate(['notes' => ['required', 'string', 'min:10']]);
        $report = $this->reports->reject($id, $request->user()->actor_id, $request->notes);
        return response()->json(['message' => 'Report rejected.', 'data' => $this->formatReport($report)]);
    }

    // ── Private helper ─────────────────────────────────────────────────────

    private function formatReport(DailyReport $report): array
    {
        $data = $report->toArray();

        // ── Officer details ────────────────────────────────────────────────
        $officerActor = Actor::find($report->officer_actor_id);
        $officerUser  = $officerActor
            ? User::where('actor_id', $officerActor->id)->first()
            : null;

        $officerMembership = $officerUser
            ? \Modules\Platform\Models\OrgMembership::where('user_id', $officerUser->id)
                ->where('org_id', $report->org_id)
                ->where('status', 'active')
                ->with('orgRole')
                ->first()
            : null;

        $data['officer_id']     = $report->officer_actor_id;
        $data['officer_name']   = $officerActor?->display_name;
        $data['officer_email']  = $officerUser?->email;
        $data['officer_phone']  = null;
        $data['officer_role']   = $officerMembership?->orgRole?->name;
        $data['officer_status'] = $officerUser?->status;

        // ── Reviewer details ───────────────────────────────────────────────
        $reviewerActor = $report->reviewed_by
            ? Actor::find($report->reviewed_by)
            : null;

        $reviewerUser = $reviewerActor
            ? User::where('actor_id', $reviewerActor->id)->first()
            : null;

        $reviewerMembership = $reviewerUser
            ? \Modules\Platform\Models\OrgMembership::where('user_id', $reviewerUser->id)
                ->where('org_id', $report->org_id)
                ->where('status', 'active')
                ->with('orgRole')
                ->first()
            : null;

        $data['reviewed_by_name'] = $reviewerActor?->display_name;
        $data['reviewed_by_role'] = $reviewerMembership?->orgRole?->name;
        $data['review_decision']  = $report->status; // approved|rejected
        $data['admin_feedback']   = $report->review_notes;

        return $data;
    }
}