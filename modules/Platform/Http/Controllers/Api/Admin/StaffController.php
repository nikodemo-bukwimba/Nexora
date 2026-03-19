<?php

namespace Modules\Platform\Http\Controllers\Api\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\PlatformAdminServiceInterface;
use Modules\Platform\Http\Requests\Admin\AssignStaffRoleRequest;

class StaffController extends Controller
{
    public function __construct(
        protected PlatformAdminServiceInterface $admin
    ) {}

    /** GET /api/v1/admin/staff */
    public function index(Request $request): JsonResponse
    {
        $staff = $this->admin->listStaff(
            $request->only(['search']),
            (int) $request->get('per_page', 25)
        );

        return response()->json($staff);
    }

    /** POST /api/v1/admin/staff */
    public function assign(AssignStaffRoleRequest $request): JsonResponse
    {
        $this->admin->assignStaffRole(
            $request->user_id,
            $request->role_name,
            $request->user()->id
        );

        return response()->json(['message' => 'Platform role assigned.'], 201);
    }

    /** DELETE /api/v1/admin/staff/{userId}/{roleName} */
    public function revoke(Request $request, string $userId, string $roleName): JsonResponse
    {
        $this->admin->revokeStaffRole($userId, $roleName);

        return response()->json(['message' => 'Platform role revoked.']);
    }
}
