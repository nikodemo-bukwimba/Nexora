<?php

namespace Modules\Platform\Http\Controllers\Api\Org;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\OrganizationServiceInterface;
use Modules\Platform\Http\Requests\Org\PermissionRequestRequest;

class OrgPermissionRequestController extends Controller
{
    public function __construct(
        protected OrganizationServiceInterface $orgService
    ) {
    }

    /** GET /api/v1/orgs/{orgId}/permission-requests */
    public function index(Request $request, string $orgId): JsonResponse
    {
        $requests = $this->orgService->listPermissionRequests(
            $orgId,
            $request->get('status', 'pending')
        );

        return response()->json($requests);
    }

    /** POST /api/v1/orgs/{orgId}/permission-requests */
    public function store(PermissionRequestRequest $request, string $orgId): JsonResponse
    {
        $permRequest = $this->orgService->requestPermission(
            $orgId,
            $request->org_role_id,
            $request->org_permission_def_id,
            $request->reason,
            $request->user()->id
        );

        return response()->json(['message' => 'Permission request submitted.', 'request' => $permRequest], 201);
    }

    /** POST /api/v1/orgs/{orgId}/permission-requests/{requestId}/approve */
    public function approve(Request $request, string $orgId, string $requestId): JsonResponse
    {
        $permRequest = $this->orgService->approvePermissionRequest($requestId, $request->user()->id);
        return response()->json(['message' => 'Request approved.', 'request' => $permRequest]);
    }

    /** POST /api/v1/orgs/{orgId}/permission-requests/{requestId}/deny */
    public function deny(Request $request, string $orgId, string $requestId): JsonResponse
    {
        $permRequest = $this->orgService->denyPermissionRequest($requestId, $request->user()->id);
        return response()->json(['message' => 'Request denied.', 'request' => $permRequest]);
    }
}
