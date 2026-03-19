<?php

namespace Modules\Platform\Repositories;

use Modules\Platform\Contracts\Repositories\OrgMembershipRepositoryInterface;
use Modules\Platform\Models\OrgMembership;

class OrgMembershipRepository implements OrgMembershipRepositoryInterface
{
    public function findByUserAndOrg(string $userId, string $orgId): ?OrgMembership
    {
        return OrgMembership::where('user_id', $userId)
            ->where('org_id', $orgId)
            ->first();
    }

    public function findByToken(string $token): ?OrgMembership
    {
        return OrgMembership::where('invite_token', $token)->first();
    }

    public function hasLevel(string $userId, string $orgId, int $minLevel): bool
    {
        return OrgMembership::where('user_id', $userId)
            ->where('org_id', $orgId)
            ->where('status', 'active')
            ->where('level', '>=', $minLevel)
            ->exists();
    }

    public function create(array $data): OrgMembership
    {
        return OrgMembership::create($data);
    }

    public function update(OrgMembership $membership, array $data): OrgMembership
    {
        $membership->fill($data)->save();
        return $membership->fresh();
    }

    public function delete(OrgMembership $membership): void
    {
        $membership->delete();
    }
}
