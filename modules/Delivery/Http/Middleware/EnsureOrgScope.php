<?php

// === FILE: Modules/Delivery/Http/Middleware/EnsureOrgScope.php

namespace Modules\Delivery\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Platform\Contracts\Services\OrgScopeResolverInterface;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrgScope
{
    public function __construct(
        protected OrgScopeResolverInterface $scope,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $routeOrgId = $request->route('orgId');

        if (! $routeOrgId) {
            return $next($request);
        }

        // Load all active membership org IDs for this user
        $memberOrgIds = DB::connection('platform')
            ->table('org_memberships')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('org_id')
            ->toArray();

        if (empty($memberOrgIds)) {
            return response()->json([
                'message' => 'Forbidden. You have no active organization memberships.',
            ], 403);
        }

        // Verify the user has access to the requested org —
        // either as a direct member or as a root org member accessing a branch.
        $routeOrg = $this->scope->find($routeOrgId);

        $hasAccess = in_array($routeOrgId, $memberOrgIds)
            || ($routeOrg->root_org_id && in_array($routeOrg->root_org_id, $memberOrgIds));

        if (! $hasAccess) {
            return response()->json([
                'message' => 'Forbidden. You do not have access to this organization.',
            ], 403);
        }

        // Resolve the effective scope using the same OrgScopeResolver
        // used across all other modules:
        //   Root member  → scopeIds returns entire tree (root + all branches)
        //   Branch member → scopeIds returns [branchId] only
        //
        // The controller reads scopeOrgIds for list queries (whereIn)
        // and effectiveOrgId for single-record writes (store/update/delete).
        $scopeOrgIds    = $this->scope->scopeIds($routeOrgId);
        $effectiveOrgId = $this->scope->isRoot($routeOrgId)
            ? $routeOrgId   // root admin — writes go to root org
            : $routeOrgId;  // branch user — writes go to their branch

        $request->merge([
            'effectiveOrgId' => $effectiveOrgId,
            'scopeOrgIds'    => $scopeOrgIds,
        ]);

        return $next($request);
    }
}