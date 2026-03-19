<?php

namespace Modules\Platform\Contracts\Services;

use Modules\Platform\Models\OrgMembership;

interface OrgMembershipServiceInterface
{
    public function invite(string $orgId, string $email, string $roleId, int $level, string $invitedBy): OrgMembership;
    public function acceptInvite(string $membershipId, string $userId): OrgMembership;
    public function declineInvite(string $membershipId, string $userId): void;
    public function removeMember(string $orgId, string $userId, string $removedBy): void;
    public function updateMember(string $membershipId, array $data, string $updatedBy): OrgMembership;
    public function listMembers(string $orgId, array $filters, int $perPage): \Illuminate\Pagination\LengthAwarePaginator;
    public function getMembership(string $orgId, string $userId): ?OrgMembership;
}
