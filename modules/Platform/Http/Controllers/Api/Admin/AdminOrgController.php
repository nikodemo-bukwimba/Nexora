<?php

namespace Modules\Platform\Http\Controllers\Api\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\PlatformAdminServiceInterface;
use Modules\Platform\Http\Requests\Admin\RejectOrgRequest;

class AdminOrgController extends Controller
{
    public function __construct(
        protected PlatformAdminServiceInterface $admin
    ) {}

    /** GET /api/v1/admin/orgs */
    public function index(Request $request): JsonResponse
    {
        $orgs = $this->admin->listOrganizations(
            $request->only(['status', 'search']),
            (int) $request->get('per_page', 25)
        );

        return response()->json($orgs);
    }

    /** GET /api/v1/admin/orgs/{id} */
    public function show(string $id): JsonResponse
    {
        $org = $this->admin->getOrganization($id);

        return response()->json($org);
    }

    /** POST /api/v1/admin/orgs/{id}/approve */
    public function approve(Request $request, string $id): JsonResponse
    {
        $org = $this->admin->approveOrganization($id, $request->user()->id);

        return response()->json([
            'message' => 'Organization approved.',
            'org'     => $org,
        ]);
    }

    /** POST /api/v1/admin/orgs/{id}/reject */
    public function reject(RejectOrgRequest $request, string $id): JsonResponse
    {
        $org = $this->admin->rejectOrganization(
            $id,
            $request->user()->id,
            $request->reason
        );

        return response()->json([
            'message' => 'Organization rejected.',
            'org'     => $org,
        ]);
    }

    /** POST /api/v1/admin/orgs/{id}/suspend */
    public function suspend(Request $request, string $id): JsonResponse
    {
        $org = $this->admin->suspendOrganization($id, $request->user()->id);

        return response()->json([
            'message' => 'Organization suspended.',
            'org'     => $org,
        ]);
    }

    /** POST /api/v1/admin/orgs/{id}/reactivate */
    public function reactivate(Request $request, string $id): JsonResponse
    {
        $org = $this->admin->reactivateOrganization($id, $request->user()->id);

        return response()->json([
            'message' => 'Organization reactivated.',
            'org'     => $org,
        ]);
    }
}
