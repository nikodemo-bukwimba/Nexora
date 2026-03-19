<?php

namespace Modules\Platform\Http\Controllers\Api\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\PlatformAdminServiceInterface;
use Modules\Platform\Http\Requests\Admin\AssignUserTierRequest;
use Modules\Platform\Http\Requests\Admin\UpdateUserStatusRequest;

class AdminUserController extends Controller
{
    public function __construct(
        protected PlatformAdminServiceInterface $admin
    ) {}

    /** GET /api/v1/admin/users */
    public function index(Request $request): JsonResponse
    {
        $users = $this->admin->listUsers(
            $request->only(['status', 'search']),
            (int) $request->get('per_page', 25)
        );

        return response()->json($users);
    }

    /** PATCH /api/v1/admin/users/{id}/status */
    public function updateStatus(UpdateUserStatusRequest $request, string $id): JsonResponse
    {
        $user = $this->admin->updateUserStatus($id, $request->status);

        return response()->json([
            'message' => "User status updated to {$request->status}.",
            'user'    => $user,
        ]);
    }

    /** POST /api/v1/admin/users/{id}/tier */
    public function assignTier(AssignUserTierRequest $request, string $id): JsonResponse
    {
        $this->admin->assignUserTier($id, $request->tier_name, $request->user()->id);

        return response()->json(['message' => 'Tier assigned.'], 201);
    }
}
