<?php

namespace Modules\Platform\Contracts\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Platform\Models\OrgMembership;

interface MembershipServiceInterface
{
    public function invite(string $orgId, string $email, string $roleId, int $level, string $invitedBy): OrgMembership;
    public function acceptInvite(string $orgId, string $userId): OrgMembership;
    public function declineInvite(string $orgId, string $userId): void;
    public function removeMember(string $orgId, string $userId, string $removedBy): void;
    public function listMembers(string $orgId, array $filters, int $perPage): LengthAwarePaginator;
    public function updateMember(string $orgId, string $userId, array $data, string $updatedBy): OrgMembership;
}
