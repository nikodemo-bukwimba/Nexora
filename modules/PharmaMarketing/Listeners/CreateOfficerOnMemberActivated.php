<?php
namespace Modules\PharmaMarketing\Listeners;

use Modules\Platform\Events\MemberActivated;
use Modules\Platform\Models\OrgMembership;
use Modules\PharmaMarketing\Services\OfficerService;

class CreateOfficerOnMemberActivated
{
    public function __construct(protected OfficerService $officers) {}

    public function handle(MemberActivated $event): void
    {
        $membership = OrgMembership::with(['user.actor', 'orgRole', 'organization'])
            ->find($event->membership->id);

        if (! $membership) return;

        $roleSlug = strtolower($membership->orgRole?->slug ?? $membership->orgRole?->name ?? '');

        // Only create pm_officer for officer-type roles
        $isOfficerRole = str_contains($roleSlug, 'officer')
            || str_contains($roleSlug, 'field')
            || str_contains($roleSlug, 'pharma_rep')
            || str_contains($roleSlug, 'sales_rep');

        if (! $isOfficerRole) return;

        $user = $membership->user;
        if (! $user) return;

        $rootOrgId = $membership->organization?->root_org_id ?? $membership->org_id;

        $this->officers->createFromAdminOrg(
            orgId:          $rootOrgId,
            branchId:       $membership->org_id,
            platformUserId: $user->id,
            actorId:        $user->actor_id ?? '',
            name:           $user->actor?->display_name ?? $user->username,
            email:          $user->email,
            source:         'admin',
        );
    }
}