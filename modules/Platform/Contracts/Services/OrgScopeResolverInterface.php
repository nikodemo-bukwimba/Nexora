<?php

namespace Modules\Platform\Contracts\Services;

interface OrgScopeResolverInterface
{
    /**
     * Return all org IDs the caller is allowed to query.
     *
     * Root org  → [root_id, branch1_id, branch2_id, ...]  (entire tree)
     * Branch    → [branch_id]                               (own branch only)
     *
     * Optional $branchId filter: root admin can narrow to one branch.
     */
    public function scopeIds(string $orgId, ?string $branchId = null): array;

    /**
     * Always return the root org ID of the given org.
     * Use this for catalog-level resources (products, pricing).
     */
    public function rootId(string $orgId): string;

    /**
     * True when the given org is a root-type org.
     */
    public function isRoot(string $orgId): bool;

    /**
     * Return the Organisation model for the given ID.
     * Throws ModelNotFoundException if not found.
     */
    public function find(string $orgId): \Modules\Platform\Models\Organization;
}