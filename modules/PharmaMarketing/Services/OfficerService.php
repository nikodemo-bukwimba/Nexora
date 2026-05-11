<?php
// FILE: modules/PharmaMarketing/Services/OfficerService.php
// CHANGE: Added transferOfficer() method. All existing methods unchanged.

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
                'org_id'              => $orgId,
                'branch_id'           => $branchId,
                'platform_user_id'    => $platformUserId,
                'actor_id'            => $actorId,
                'registration_source' => $source,
                'name'                => $name,
                'email'               => $email,
                'phone'               => $phone,
                'status'              => 'active',
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

    // ── NEW ──────────────────────────────────────────────────────────────

    /**
     * HQ-only: transfer an officer to a different branch.
     *
     * This is the SINGLE authoritative transfer pipeline.
     * It does three things atomically:
     *   1. Updates pm_officers.branch_id (+ audit columns)
     *   2. Marks the old org_memberships row as 'transferred'
     *   3. Creates (or re-activates) the new branch membership
     *
     * @param  string $platformUserId  The platform users.id of the officer
     * @param  string $rootOrgId       The root org the officer belongs to
     * @param  string $newBranchId     Target branch org_id
     * @param  string $newOrgRoleId    Role to assign in the new branch
     * @param  string $transferredBy   platform users.id of the HQ admin doing this
     */
    public function transferOfficer(
        string $platformUserId,
        string $rootOrgId,
        string $newBranchId,
        string $newOrgRoleId,
        string $transferredBy,
    ): PmOfficer {
        return DB::connection('pharma_marketing')->transaction(function () use (
            $platformUserId,
            $rootOrgId,
            $newBranchId,
            $newOrgRoleId,
            $transferredBy,
        ) {
            // 1. Load officer — fails loudly if not found
            $officer = PmOfficer::where('platform_user_id', $platformUserId)
                ->where('org_id', $rootOrgId)
                ->firstOrFail();

            // No-op guard
            if ($officer->branch_id === $newBranchId) {
                return $officer;
            }

            $oldBranchId = $officer->branch_id;

            // 2. Update pm_officers — sets branch_id + audit columns
            $officer->transferToBranch($newBranchId, $transferredBy);

            // 3. Mark old platform membership as 'transferred' (keeps audit row)
            DB::connection('platform')
                ->table('org_memberships')
                ->where('user_id', $platformUserId)
                ->where('org_id', $oldBranchId)
                ->update([
                    'status'     => 'transferred',
                    'updated_at' => now(),
                ]);

            // 4. Insert or re-activate the new branch membership
            $existing = DB::connection('platform')
                ->table('org_memberships')
                ->where('user_id', $platformUserId)
                ->where('org_id', $newBranchId)
                ->first();

            if (! $existing) {
                DB::connection('platform')
                    ->table('org_memberships')
                    ->insert([
                        'id'          => (string) str()->ulid(),
                        'user_id'     => $platformUserId,
                        'org_id'      => $newBranchId,
                        'org_role_id' => $newOrgRoleId,
                        'level'       => 0,
                        'status'      => 'active',
                        'joined_at'   => now(),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
            } else {
                // Row exists (e.g. a previous transfer to this same branch):
                // update the role and re-activate it
                DB::connection('platform')
                    ->table('org_memberships')
                    ->where('user_id', $platformUserId)
                    ->where('org_id', $newBranchId)
                    ->update([
                        'org_role_id' => $newOrgRoleId,
                        'status'      => 'active',
                        'updated_at'  => now(),
                    ]);
            }

            return $officer->fresh();
        });
    }

    // ─────────────────────────────────────────────────────────────────────
}
