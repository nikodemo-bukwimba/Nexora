<?php

namespace Modules\PharmaMarketing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PharmaMarketing\Services\FieldVisitService;

class FieldVisitController extends Controller
{
    public function __construct(protected FieldVisitService $visits) {}

    /** GET /api/v1/pharma/orgs/{orgId}/visits */
    public function index(Request $request, string $orgId): JsonResponse
    {
        return response()->json($this->visits->list($orgId, $request->only(['officer_id', 'customer_id', 'status', 'date', 'from', 'to']), (int) $request->get('per_page', 25)));
    }

    /** POST /api/v1/pharma/orgs/{orgId}/visits/check-in */
    public function checkIn(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'customer_id' => ['required', 'string', 'size:26'],
        ]);

        $visit = $this->visits->checkIn($orgId, $request->user()->actor_id, $request->customer_id, $request->all());
        return response()->json(['message' => 'Checked in.', 'visit' => $visit], 201);
    }

    /** PATCH /api/v1/pharma/visits/{id}/check-out */
    public function checkOut(Request $request, string $id): JsonResponse
    {
        $visit = $this->visits->checkOut($id, $request->user()->actor_id, $request->all());
        return response()->json(['message' => 'Visit completed.', 'visit' => $visit]);
    }

    /** GET /api/v1/pharma/visits/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json($this->visits->get($id));
    }

    /** POST /api/v1/pharma/visits/{id}/attachments */
    public function uploadAttachment(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
        ]);

        $attachment = $this->visits->uploadAttachment($id, $request->user()->actor_id, $request->file('file'), $request->only(['caption', 'latitude', 'longitude']));
        return response()->json(['message' => 'Attachment uploaded.', 'attachment' => $attachment], 201);
    }

/**
 * POST /api/v1/pharma/visits/{id}/review
 */
public function review(Request $request, string $id): \Illuminate\Http\JsonResponse
{
    $visit = \Modules\PharmaMarketing\Models\FieldVisit::findOrFail($id);

    $visit->update([
        'admin_status' => 'reviewed',
        'admin_notes'  => $request->input('notes'),
        'reviewed_by'  => $request->user()->id,
        'reviewed_at'  => now(),
    ]);

    return response()->json([
        'message' => 'Visit reviewed.',
        'visit'   => $visit->fresh(['customer', 'attachments', 'products']),
    ]);
}

/**
 * POST /api/v1/pharma/visits/{id}/flag
 */
public function flag(Request $request, string $id): \Illuminate\Http\JsonResponse
{
    $request->validate([
        'reason' => ['required', 'string', 'min:5', 'max:1000'],
    ]);

    $visit = \Modules\PharmaMarketing\Models\FieldVisit::findOrFail($id);

    $visit->update([
        'admin_status' => 'flagged',
        'flag_reason'  => $request->reason,
        'reviewed_by'  => $request->user()->id,
        'reviewed_at'  => now(),
    ]);

    return response()->json([
        'message' => 'Visit flagged.',
        'visit'   => $visit->fresh(['customer', 'attachments', 'products']),
    ]);
}
}
