<?php

// === FILE: Modules/Delivery/Http/Middleware/EnsureOrgScope.php

namespace Modules\Delivery\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Platform\Contracts\Services\OrgScopeResolverInterface;
use Modules\Platform\Models\Organization;
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

        // ── Staff / admin path ─────────────────────────────────────────
        $memberOrgIds = DB::connection('platform')
            ->table('org_memberships')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('org_id')
            ->toArray();

        if (! empty($memberOrgIds)) {
            $routeOrg = $this->scope->find($routeOrgId);

            $hasAccess = in_array($routeOrgId, $memberOrgIds)
                || ($routeOrg->root_org_id && in_array($routeOrg->root_org_id, $memberOrgIds));

            if (! $hasAccess) {
                return response()->json([
                    'message' => 'Forbidden. You do not have access to this organization.',
                ], 403);
            }

            $request->merge([
                'effectiveOrgId' => $routeOrgId,
                'scopeOrgIds'    => $this->scope->scopeIds($routeOrgId),
                'actorRole'      => 'staff',
            ]);

            return $next($request);
        }

        // ── Customer bypass ────────────────────────────────────────────
        // Customers have no org_membership but may read deliveries linked
        // to their own orders. Deliveries may be stored under any branch,
        // so scopeOrgIds must cover the full org tree (root + branches).
        $rootOrgId = $this->scope->rootId($routeOrgId);

        $treeOrgIds = Organization::where('root_org_id', $rootOrgId)
            ->orWhere('id', $rootOrgId)
            ->pluck('id')
            ->toArray();

        $customer = DB::connection('pharma_marketing')
            ->table('pm_customers')
            ->where('platform_user_id', $user->id)
            ->whereIn('org_id', $treeOrgIds)
            ->first();

        if (! $customer) {
            return response()->json([
                'message' => 'Forbidden. You do not have access to this organization.',
            ], 403);
        }

        // Give the customer read access across the full tree so they can
        // find their delivery regardless of which branch org_id it was
        // stored under.
        $request->merge([
            'effectiveOrgId'  => $rootOrgId,
            'scopeOrgIds'     => $treeOrgIds,
            'actorRole'       => 'customer',
            'customerActorId' => $user->actor_id,
            'customerId'      => $customer->id,
        ]);

        return $next($request);
    }
}