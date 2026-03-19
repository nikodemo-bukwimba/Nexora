<?php

namespace Modules\Platform\Http\Controllers\Api\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\PlatformAdminServiceInterface;
use Modules\Platform\Http\Requests\Admin\ToggleFlagRequest;

class FeatureFlagController extends Controller
{
    public function __construct(
        protected PlatformAdminServiceInterface $admin
    ) {}

    /** GET /api/v1/admin/flags */
    public function index(): JsonResponse
    {
        return response()->json($this->admin->listFlags());
    }

    /** PATCH /api/v1/admin/flags/{key} */
    public function toggle(ToggleFlagRequest $request, string $key): JsonResponse
    {
        $flag = $this->admin->toggleFlag($key, $request->value, $request->user()->id);

        return response()->json([
            'message' => "Flag '{$key}' updated.",
            'flag'    => $flag,
        ]);
    }
}
