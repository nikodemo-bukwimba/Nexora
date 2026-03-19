<?php

namespace Modules\Platform\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Platform\Contracts\Repositories\OrganizationRepositoryInterface;
use Modules\Platform\Models\Organization;

class OrganizationRepository implements OrganizationRepositoryInterface
{
    public function findById(string $id): ?Organization
    {
        return Organization::find($id);
    }

    public function findBySlug(string $slug): ?Organization
    {
        return Organization::where('slug', $slug)->first();
    }

    public function create(array $data): Organization
    {
        return Organization::create($data);
    }

    public function update(Organization $org, array $data): Organization
    {
        $org->fill($data)->save();
        return $org->fresh();
    }

    public function getDescendants(string $orgId): Collection
    {
        $org = Organization::findOrFail($orgId);
        return Organization::whereRaw('path <@ ?', [$org->path])
            ->where('id', '!=', $orgId)
            ->orderByRaw('nlevel(path)')
            ->get();
    }

    public function getAncestors(string $orgId): Collection
    {
        $org = Organization::findOrFail($orgId);
        return Organization::whereRaw('path @> ?', [$org->path])
            ->where('id', '!=', $orgId)
            ->orderByRaw('nlevel(path)')
            ->get();
    }

    public function getTree(string $rootOrgId): Collection
    {
        $root = Organization::findOrFail($rootOrgId);
        return Organization::whereRaw('path <@ ?', [$root->path])
            ->orderByRaw('nlevel(path)')
            ->get();
    }
}
