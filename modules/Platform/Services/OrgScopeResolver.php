<?php

namespace Modules\Platform\Services;

use Modules\Platform\Contracts\Services\OrgScopeResolverInterface;
use Modules\Platform\Models\Organization;

/**
 * OrgScopeResolver
 * ─────────────────────────────────────────────────────────────
 * Single source of truth for multi-branch data scoping.
 *
 * RULES:
 *   Root admin  → sees ALL data in the entire org tree
 *   Branch user → sees ONLY their branch data
 *
 * USAGE IN SERVICES / CONTROLLERS:
 *
 *   // Inject via constructor
 *   public function __construct(
 *       protected OrgScopeResolverInterface $scope
 *   ) {}
 *
 *   // Scope any query
 *   $orgIds = $this->scope->scopeIds($orgId);
 *   $items  = Model::whereIn('org_id', $orgIds)->paginate();
 *
 *   // Root admin filtering down to one branch
 *   $orgIds = $this->scope->scopeIds($orgId, $request->branch_id);
 *
 *   // Catalog / product queries — always root
 *   $rootId   = $this->scope->rootId($orgId);
 *   $products = Product::where('org_id', $rootId)->paginate();
 */
class OrgScopeResolver implements OrgScopeResolverInterface
{
    /** Lightweight cache within a single request lifecycle */
    private array $cache = [];

    public function scopeIds(string $orgId, ?string $branchId = null): array
    {
        $org = $this->find($orgId);

        // Branch user — always scoped to their own branch only
        if ($org->type !== 'root') {
            return [$orgId];
        }

        // Root admin filtered to a specific branch
        if ($branchId !== null) {
            return [$branchId];
        }

        // Root admin — entire tree
        $cacheKey = "tree_{$orgId}";
        if (! isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = Organization::where('root_org_id', $orgId)
                ->orWhere('id', $orgId)
                ->pluck('id')
                ->toArray();
        }

        return $this->cache[$cacheKey];
    }

    public function rootId(string $orgId): string
    {
        $org = $this->find($orgId);
        return $org->root_org_id ?? $orgId;
    }

    public function isRoot(string $orgId): bool
    {
        return $this->find($orgId)->type === 'root';
    }

    public function find(string $orgId): Organization
    {
        $cacheKey = "org_{$orgId}";
        if (! isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = Organization::findOrFail($orgId);
        }
        return $this->cache[$cacheKey];
    }
}