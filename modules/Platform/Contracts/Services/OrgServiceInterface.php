<?php

namespace Modules\Platform\Contracts\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Platform\Models\Organization;

interface OrgServiceInterface
{
    public function createRootOrg(array $data, string $userId): Organization;
    public function createBranch(array $data, string $parentId, string $userId): Organization;
    public function getBySlug(string $slug): Organization;
    public function getTree(string $rootOrgId): array;
    public function update(string $orgId, array $data, string $userId): Organization;
}
