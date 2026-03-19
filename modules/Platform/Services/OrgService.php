<?php

namespace Modules\Platform\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Platform\Contracts\Services\OrgServiceInterface;
use Modules\Platform\Models\Actor;
use Modules\Platform\Models\ActorType;
use Modules\Platform\Models\ActorTypeAssignment;
use Modules\Platform\Models\Organization;

class OrgService implements OrgServiceInterface
{
    /**
     * Create a root organization.
     * Status starts as 'pending_approval' — admin must approve before it becomes active.
     * Creates an Actor for the org and assigns 'organization' actor type.
     */
    public function createRootOrg(array $data, string $userId): Organization
    {
        return DB::connection('platform')->transaction(function () use ($data, $userId) {

            // 1. Create Actor for this org
            $actor = Actor::create([
                'display_name' => $data['name'],
                'status'       => 'active',
            ]);

            // 2. Assign 'organization' actor type
            $orgType = ActorType::where('name', 'organization')->firstOrFail();
            ActorTypeAssignment::insertOrIgnore([
                'actor_id'      => $actor->id,
                'actor_type_id' => $orgType->id,
                'assigned_at'   => now(),
                'assigned_by'   => $userId,
            ]);

            // 3. Generate unique slug
            $slug = $this->generateSlug($data['name']);

            // 4. Create Organization (root — no parent, no root_org_id)
            $org = Organization::create([
                'actor_id'    => $actor->id,
                'parent_id'   => null,
                'root_org_id' => null,
                'path'        => $actor->id,   // ltree path = just own ULID for root
                'depth'       => 0,
                'name'        => $data['name'],
                'slug'        => $slug,
                'type'        => 'root',
                'status'      => 'pending_approval',
                'settings'    => $data['settings'] ?? null,
            ]);

            // 5. Update root_org_id to self now that we have the ID
            $org->update(['root_org_id' => $org->id]);

            // 6. Update ltree path to use org id (dot-separated ULIDs)
            DB::connection('platform')->statement(
                "UPDATE organizations SET path = ? WHERE id = ?",
                [$org->id, $org->id]
            );

            return $org->fresh();
        });
    }

    /**
     * Create a branch under any existing org node.
     * Branch inherits root_org_id from parent.
     * Parent org must be active.
     */
    public function createBranch(array $data, string $parentId, string $userId): Organization
    {
        return DB::connection('platform')->transaction(function () use ($data, $parentId, $userId) {

            $parent = Organization::findOrFail($parentId);

            if ($parent->status !== 'active') {
                throw new \RuntimeException('Cannot create a branch under an inactive organization.');
            }

            // 1. Create Actor for branch
            $actor = Actor::create([
                'display_name' => $data['name'],
                'status'       => 'active',
            ]);

            // 2. Assign 'organization' actor type
            $orgType = ActorType::where('name', 'organization')->firstOrFail();
            ActorTypeAssignment::insertOrIgnore([
                'actor_id'      => $actor->id,
                'actor_type_id' => $orgType->id,
                'assigned_at'   => now(),
                'assigned_by'   => $userId,
            ]);

            // 3. Generate slug
            $slug = $this->generateSlug($data['name']);

            // 4. Build ltree path: parent.path + . + actor.id
            $parentPath = DB::connection('platform')
                ->selectOne("SELECT path::text as path FROM organizations WHERE id = ?", [$parentId])
                ->path;
            $newPath = $parentPath . '.' . $actor->id;

            // 5. Create branch
            $branch = Organization::create([
                'actor_id'    => $actor->id,
                'parent_id'   => $parentId,
                'root_org_id' => $parent->root_org_id,
                'path'        => $parentPath, // temp, updated via raw below
                'depth'       => $parent->depth + 1,
                'name'        => $data['name'],
                'slug'        => $slug,
                'type'        => 'branch',
                'status'      => 'active',  // branches are active immediately
                'settings'    => $data['settings'] ?? null,
            ]);

            // 6. Set ltree path properly
            DB::connection('platform')->statement(
                "UPDATE organizations SET path = ? WHERE id = ?",
                [$newPath, $branch->id]
            );

            return $branch->fresh();
        });
    }

    public function getBySlug(string $slug): Organization
    {
        return Organization::where('slug', $slug)
            ->with(['actor'])
            ->firstOrFail();
    }

    /**
     * Return the full org tree for a root org using ltree path ordering.
     */
    public function getTree(string $rootOrgId): array
    {
        $orgs = Organization::where('root_org_id', $rootOrgId)
            ->orWhere('id', $rootOrgId)
            ->orderByRaw('path')
            ->with('actor')
            ->get();

        return $this->buildTree($orgs->toArray(), $rootOrgId);
    }

    public function update(string $orgId, array $data, string $userId): Organization
    {
        $org = Organization::findOrFail($orgId);
        $org->update(array_filter([
            'name'     => $data['name'] ?? null,
            'settings' => $data['settings'] ?? null,
        ]));

        // Keep actor display_name in sync
        if (isset($data['name'])) {
            $org->actor->update(['display_name' => $data['name']]);
        }

        return $org->fresh(['actor']);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function buildTree(array $orgs, string $parentId, int $depth = 0): array
    {
        $tree = [];
        foreach ($orgs as $org) {
            if ($depth === 0 && $org['id'] === $parentId && $org['parent_id'] === null) {
                $org['children'] = $this->buildTree($orgs, $org['id'], $depth + 1);
                $tree[] = $org;
            } elseif ($org['parent_id'] === $parentId) {
                $org['children'] = $this->buildTree($orgs, $org['id'], $depth + 1);
                $tree[] = $org;
            }
        }
        return $tree;
    }
}
