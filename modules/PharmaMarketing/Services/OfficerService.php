<?php
namespace Modules\PharmaMarketing\Services;

use Illuminate\Support\Facades\DB;
use Modules\PharmaMarketing\Models\PmOfficer;

class OfficerService
{
    /**
     * Called by AuthController after officer invite is accepted OR
     * after admin creates an officer via the org members endpoint.
     * Creates a pm_officers record linked to their platform user.
     * Idempotent — safe to call multiple times.
     */
    public function createFromAdminOrg(
        string $orgId,
        string $branchId,
        string $platformUserId,
        string $actorId,
        string $name,
        ?string $email = null,
        ?string $phone = null,
        string $source = 'admin',
    ): PmOfficer {
        return DB::connection('pharma_marketing')->transaction(function () use (
            $orgId, $branchId, $platformUserId, $actorId, $name, $email, $phone, $source
        ) {
            $existing = PmOfficer::where('platform_user_id', $platformUserId)
                ->where('org_id', $orgId)
                ->first();

            if ($existing) {
                return $existing;
            }

            return PmOfficer::create([
                'org_id'               => $orgId,
                'branch_id'            => $branchId,
                'platform_user_id'     => $platformUserId,
                'actor_id'             => $actorId,
                'registration_source'  => $source,
                'name'                 => $name,
                'email'                => $email,
                'phone'                => $phone,
                'status'               => 'active',
            ]);
        });
    }

    /**
     * Link existing pm_officer record to a platform user who self-registered.
     * Matches by email.
     */
    public function linkPlatformUser(
        string $orgId,
        string $platformUserId,
        string $actorId,
        string $email
    ): ?PmOfficer {
        $officer = PmOfficer::where('org_id', $orgId)
            ->where('email', $email)
            ->whereNull('platform_user_id')
            ->first();

        if ($officer) {
            $officer->update([
                'platform_user_id'    => $platformUserId,
                'actor_id'            => $actorId,
                'registration_source' => 'self_registered',
            ]);
            return $officer->fresh();
        }

        return null;
    }
}