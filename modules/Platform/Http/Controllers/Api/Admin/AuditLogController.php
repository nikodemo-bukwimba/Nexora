<?php

namespace Modules\Platform\Http\Controllers\Api\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\PlatformAdminServiceInterface;

class AuditLogController extends Controller
{
    public function __construct(
        protected PlatformAdminServiceInterface $admin
    ) {}

    /** GET /api/v1/admin/audit */
    public function index(Request $request): JsonResponse
    {
        $logs = $this->admin->queryAuditLog(
            $request->only(['module', 'action', 'actor_id', 'subject_type', 'subject_id', 'from', 'to']),
            (int) $request->get('per_page', 50)
        );

        return response()->json($logs);
    }
}
