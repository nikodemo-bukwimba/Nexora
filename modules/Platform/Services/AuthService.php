<?php
// FILE: modules/Platform/Services/AuthService.php
// CHANGE: autoAssignToDefaultOrg() — role priority updated so self-registered
//         users get 'user' or 'viewer' role, never 'branch_manager'.
//         Status stays 'pending_approval' (you already changed this).
//         Everything else in this file is unchanged.

namespace Modules\Platform\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Platform\Contracts\Repositories\ActorRepositoryInterface;
use Modules\Platform\Contracts\Repositories\UserRepositoryInterface;
use Modules\Platform\Contracts\Services\AuditLoggerInterface;
use Modules\Platform\Contracts\Services\AuthServiceInterface;
use Modules\Platform\Contracts\Services\EventBusInterface;
use Modules\Platform\Events\UserRegistered;
use Modules\Platform\Models\OrgMembership;
use Modules\Platform\Models\OrgRole;
use Modules\Platform\Models\PlatformTier;
use Modules\Platform\Models\User;
use Modules\Platform\Models\UserTierAssignment;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface  $users,
        protected ActorRepositoryInterface $actors,
        protected EventBusInterface        $eventBus,
        protected AuditLoggerInterface     $audit,
    ) {}

    public function register(array $data): User
    {
        return DB::connection('platform')->transaction(function () use ($data) {

            $displayName = !empty(trim($data['name'] ?? ''))
                ? trim($data['name'])
                : $data['username'];

            // 1. Create Actor
            $actor = $this->actors->create([
                'display_name' => $displayName,
                'status'       => 'active',
            ]);

            // 2. Create User
            $user = $this->users->create([
                'username' => $data['username'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'actor_id' => $actor->id,
                'status'   => 'active',
            ]);

            // 3. Assign 'user' actor type
            $this->actors->assignType($actor->id, 'user');

            // 4. Assign default tier
            $defaultTier = PlatformTier::where('is_default', true)
                ->where('is_active', true)
                ->first();

            if ($defaultTier) {
                UserTierAssignment::create([
                    'user_id'     => $user->id,
                    'tier_id'     => $defaultTier->id,
                    'assigned_by' => null,
                    'status'      => 'active',
                ]);
            }

            // 5. Auto-assign to default org if configured
            $this->autoAssignToDefaultOrg($user);

            // 6. Fire event
            $this->eventBus->fire(new UserRegistered($user), $actor->id);

            // 7. Audit
            $this->audit->log(
                module:      'platform',
                action:      'user.registered',
                subjectType: 'User',
                subjectId:   $user->id,
                newValues:   [
                    'username'     => $user->username,
                    'email'        => $user->email,
                    'display_name' => $displayName,
                ],
                actorId:     $actor->id
            );

            return $user;
        });
    }

    /**
     * Auto-assign a newly self-registered user to the platform's default org.
     *
     * CHANGES from original:
     *   1. Role priority: 'user' first, then 'viewer', then 'customer',
     *      then first non-system role. This prevents branch_manager or any
     *      privileged role from being assigned to self-registered users.
     *   2. Status: 'pending_approval' — user is blocked until admin activates.
     *      (You already changed this manually; confirmed here.)
     */
    private function autoAssignToDefaultOrg(User $user): void
    {
        try {
            $flag = DB::connection('platform')
                ->table('platform_feature_flags')
                ->where('key', 'platform.default_org_id')
                ->where('value', true)
                ->first();

            if (! $flag || empty($flag->description)) {
                return;
            }

            $defaultOrgId = $flag->description;

            $org = DB::connection('platform')
                ->table('organizations')
                ->where('id', $defaultOrgId)
                ->where('status', 'active')
                ->first();

            if (! $org) {
                return;
            }

            // ── CHANGE: role priority ─────────────────────────────────────────
            // 'user' → 'viewer' → 'customer' → first non-system role.
            // 'branch_manager' and any other privileged role will never match
            // unless it is literally the only role in the org (edge case).
            $role = OrgRole::where('root_org_id', $defaultOrgId)
                ->whereIn('slug', ['user', 'viewer', 'customer'])
                ->orderByRaw("CASE slug
                    WHEN 'user'     THEN 0
                    WHEN 'viewer'   THEN 1
                    WHEN 'customer' THEN 2
                    END")
                ->first();

            if (! $role) {
                // Fallback: first non-system role that is NOT a manager/admin slug
                $role = OrgRole::where('root_org_id', $defaultOrgId)
                    ->where('is_system', false)
                    ->whereNotIn('slug', [
                        'branch_manager', 'org_admin', 'admin',
                        'manager', 'super_admin', 'field_officer',
                    ])
                    ->first();
            }

            if (! $role) {
                return; // No safe role found — skip rather than assign wrong role
            }
            // ─────────────────────────────────────────────────────────────────

            $exists = OrgMembership::where('user_id', $user->id)
                ->where('org_id', $defaultOrgId)
                ->exists();

            if ($exists) {
                return;
            }

            OrgMembership::create([
                'user_id'     => $user->id,
                'org_id'      => $defaultOrgId,
                'org_role_id' => $role->id,
                'level'       => 0,
                'invited_by'  => null,
                'status'      => 'pending_approval', // blocks login until admin approves
                'joined_at'   => now(),
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                "AuthService: auto-org-assignment failed for user {$user->id}: " . $e->getMessage()
            );
        }
    }

    // ── Unchanged methods ─────────────────────────────────────────────────────

    public function loginWithToken(string $email, string $password, string $deviceName): ?string
    {
        $user = $this->users->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        if ($user->isSuspended()) {
            return null;
        }

        return $user->createToken($deviceName)->plainTextToken;
    }

    public function revokeToken(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function recordLogin(User $user, string $ip): void
    {
        $this->users->update($user, [
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}
