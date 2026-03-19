<?php

namespace Modules\Platform\Contracts\Repositories;

use Modules\Platform\Models\OrgMembership;

interface OrgMembershipRepositoryInterface
{
    public function findByUserAndOrg(string $userId, string $orgId): ?OrgMembership;
    public function findByToken(string $token): ?OrgMembership;
    public function hasLevel(string $userId, string $orgId, int $minLevel): bool;
    public function create(array $data): OrgMembership;
    public function update(OrgMembership $membership, array $data): OrgMembership;
    public function delete(OrgMembership $membership): void;
}
