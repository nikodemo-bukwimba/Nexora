<?php

namespace Modules\Communications\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Communications\Models\Community;
use Modules\Communications\Models\CommunityGroup;
use Modules\Communications\Models\CommunityMember;

class CommunityService
{
    public function create(string $createdBy, array $data): Community
    {
        $community = Community::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by'  => $createdBy,
            'org_id'      => $data['org_id'] ?? null,
            'is_public'   => $data['is_public'] ?? false,
            'status'      => 'active',
        ]);

        CommunityMember::create([
            'community_id' => $community->id,
            'actor_id'     => $createdBy,
            'role'         => 'super_admin',
            'status'       => 'active',
        ]);

        return $community->fresh(['members']);
    }

    public function get(string $id): Community
    {
        return Community::with(['members', 'groups.group'])->findOrFail($id);
    }

    public function addGroup(string $communityId, string $groupId, bool $isAnnouncementChannel = false): CommunityGroup
    {
        return CommunityGroup::create([
            'community_id'           => $communityId,
            'group_id'               => $groupId,
            'is_announcement_channel' => $isAnnouncementChannel,
        ]);
    }

    public function removeGroup(string $communityId, string $groupId): void
    {
        CommunityGroup::where('community_id', $communityId)
            ->where('group_id', $groupId)
            ->delete();
    }

    public function addMember(string $communityId, string $actorId): CommunityMember
    {
        return CommunityMember::updateOrCreate(
            ['community_id' => $communityId, 'actor_id' => $actorId],
            ['status' => 'active', 'role' => 'member']
        );
    }

    public function listPublic(int $perPage): LengthAwarePaginator
    {
        return Community::where('is_public', true)
            ->where('status', 'active')
            ->withCount('members')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
