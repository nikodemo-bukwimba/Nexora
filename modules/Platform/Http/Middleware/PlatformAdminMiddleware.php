<?php

namespace Modules\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlatformAdminMiddleware
{
    /**
     * Verify the authenticated user has a platform role with the
     * required permission. Permission is passed as a middleware argument.
     *
     * Usage in routes:
     *   ->middleware('platform.admin:orgs.approve')
     *   ->middleware('platform.admin:staff.assign')
     *
     * If no permission argument is given, just checks the user
     * has any platform role at all.
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Load user's platform roles with their permissions
        $platformRoles = \Illuminate\Support\Facades\DB::connection('platform')
            ->table('user_platform_roles as upr')
            ->join('platform_roles as pr', 'pr.id', '=', 'upr.platform_role_id')
            ->where('upr.user_id', $user->id)
            ->pluck('pr.id')
            ->toArray();

        if (empty($platformRoles)) {
            return response()->json(['message' => 'Forbidden. No platform role assigned.'], 403);
        }

        // If a specific permission is required, check it
        if ($permission) {
            $hasPermission = \Illuminate\Support\Facades\DB::connection('platform')
                ->table('platform_role_permissions as prp')
                ->join('platform_permissions as pp', 'pp.id', '=', 'prp.platform_permission_id')
                ->whereIn('prp.platform_role_id', $platformRoles)
                ->where('pp.name', $permission)
                ->where('pp.is_active', true)
                ->exists();

            if (! $hasPermission) {
                return response()->json([
                    'message'    => "Forbidden. Required permission: {$permission}",
                    'permission' => $permission,
                ], 403);
            }
        }

        // Attach resolved permissions to request for use in controllers
        $request->merge(['platform_admin_roles' => $platformRoles]);

        return $next($request);
    }
}
