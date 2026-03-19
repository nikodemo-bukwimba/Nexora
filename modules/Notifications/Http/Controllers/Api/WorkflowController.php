<?php

namespace Modules\Notifications\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notifications\Services\WorkflowService;

class WorkflowController extends Controller
{
    public function __construct(protected WorkflowService $workflows) {}

    /** GET /api/v1/workflows */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->workflows->listForOrg($request->org_id ?? null, (int) $request->get('per_page', 25)));
    }

    /** POST /api/v1/workflows */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'trigger_event' => ['required', 'string'],
            'module'        => ['required', 'string'],
            'steps'         => ['required', 'array', 'min:1'],
        ]);

        $workflow = $this->workflows->create($request->all());
        return response()->json(['message' => 'Workflow created.', 'workflow' => $workflow], 201);
    }

    /** GET /api/v1/workflows/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json($this->workflows->get($id));
    }

    /** GET /api/v1/workflows/{id}/runs */
    public function runs(Request $request, string $id): JsonResponse
    {
        return response()->json($this->workflows->listRuns($id, (int) $request->get('per_page', 25)));
    }

    /** GET /api/v1/workflows/runs/{runId} */
    public function showRun(string $runId): JsonResponse
    {
        return response()->json($this->workflows->getRun($runId));
    }
}
