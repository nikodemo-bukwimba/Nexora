<?php

namespace Modules\Platform\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Platform\Models\Organization;

interface OrganizationRepositoryInterface
{
    public function findById(string $id): ?Organization;
    public function findBySlug(string $slug): ?Organization;
    public function create(array $data): Organization;
    public function update(Organization $org, array $data): Organization;
    public function getDescendants(string $orgId): Collection;
    public function getAncestors(string $orgId): Collection;
    public function getTree(string $rootOrgId): Collection;
}
