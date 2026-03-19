<?php

namespace Modules\Platform\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Platform\Contracts\Services\OrgMembershipServiceInterface;
use Modules\Platform\Models\OrgMembership;
use Modules\Platform\Models\Organization;
use Modules\Platform\Models\User;

class OrgMembershipService implements OrgMembershipServiceInterface
{
    /**
     * Invite a user to an org node by email.
     * Creates membership in 'invited' status.
     * If user doesn't exist yet, still creates membership record
     * so invite can be accepted upon registration.
     */
    public function invite(string $orgId, string $email, string $roleId, int $level, string $invitedBy): OrgMembership
    {
        $org = Organization::findOrFail($orgId);

        if ($org->status !== 'active') {
            throw new \RuntimeException('Cannot invite members to a non-active organization.');
        }

        // Level 100 can only be granted by another level 100 member
        if ($level === 100) {
            $inviter = OrgMembership::where('user_id', $invitedBy)
                ->where('org_id', $orgId)
                ->where('status', 'active')
                ->first();

            $inviterIsLevel100 = $inviter && $inviter->level === 100;

            // Also check if inviter has tree-wide scope
            if (! $inviterIsLevel100) {
                throw new \RuntimeException('Only a level 100 member can invite another level 100 member.');
            }
        }

        $user = User::where('email', $email)->first();

        return OrgMembership::create([
            'user_id'     => $user?->id,
            'org_id'      => $orgId,
            'org_role_id' => $roleId,
            'level'       => $level,
            'invited_by'  => $invitedBy,
            'status'      => 'invited',
        ]);
    }

    public function acceptInvite(string $membershipId, string $userId): OrgMembership
    {
        $membership = OrgMembership::findOrFail($membershipId);

        if ($membership->status !== 'invited') {
            throw new \RuntimeException('Invite is no longer pending.');
        }

        $membership->update([
            'user_id'   => $userId,
            'status'    => 'active',
            'joined_at' => now(),
        ]);

        return $membership->fresh();
    }

    public function declineInvite(string $membershipId, string $userId): void
    {
        $membership = OrgMembership::where('id', $membershipId)
            ->where('user_id', $userId)
            ->where('status', 'invited')
            ->firstOrFail();

        $membership->delete();
    }

    public function removeMember(string $orgId, string $userId, string $removedBy): void
    {
        // Cannot remove yourself if you are the last level 100 member
        $membership = OrgMembership::where('org_id', $orgId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->firstOrFail();

        if ($membership->level === 100) {
            $otherLevel100Count = OrgMembership::where('org_id', $orgId)
                ->where('status', 'active')
                ->where('level', 100)
                ->where('user_id', '!=', $userId)
                ->count();

            if ($otherLevel100Count === 0) {
                throw new \RuntimeException('Cannot remove the last level 100 member from the organization.');
            }
        }

        $membership->update(['status' => 'suspended']);
    }

    public function updateMember(string $membershipId, array $data, string $updatedBy): OrgMembership
    {
        $membership = OrgMembership::findOrFail($membershipId);

        // Only higher-level members can update membership
        $updaterMembership = OrgMembership::where('user_id', $updatedBy)
            ->where('org_id', $membership->org_id)
            ->where('status', 'active')
            ->orderByDesc('level')
            ->first();

        if (! $updaterMembership || $updaterMembership->level <= $membership->level) {
            throw new \RuntimeException('You must have a higher level than the member you are updating.');
        }

        // Cannot promote to level 100 unless you are level 100
        if (isset($data['level']) && $data['level'] === 100 && $updaterMembership->level !== 100) {
            throw new \RuntimeException('Only a level 100 member can promote another member to level 100.');
        }

        $allowed = array_filter([
            'org_role_id' => $data['org_role_id'] ?? null,
            'level'       => $data['level'] ?? null,
        ], fn($v) => ! is_null($v));

        $membership->fill($allowed)->save();

        return $membership->fresh();
    }

    public function listMembers(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        return OrgMembership::where('org_id', $orgId)
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->with(['user', 'role'])
            ->orderByDesc('level')
            ->paginate($perPage);
    }

    public function getMembership(string $orgId, string $userId): ?OrgMembership
    {
        return OrgMembership::where('org_id', $orgId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();
    }
}
