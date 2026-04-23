<?php

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

            // Use 'name' (full name from Flutter) if provided.
            // Fall back to username so the actor always has a readable display name.
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
            // This ensures customer app users get an org_id immediately after
            // registration so /auth/me returns a valid org_id and the app works.
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
     * Auto-assign a newly registered user to the platform's default org
     * with the lowest-privilege role (viewer / customer).
     *
     * This is configured via the platform.default_org_id feature flag.
     * The flag's `description` column stores the org ULID.
     * The flag's `value` (boolean) acts as the on/off switch.
     *
     * To enable:
     *   INSERT INTO platform.platform_feature_flags
     *     (id, key, value, description, module)
     *   VALUES
     *     (gen_ulid(), 'platform.default_org_id', true, '{ROOT_ORG_ULID}', 'platform');
     *
     * Role priority (first match wins):
     *   1. A role with slug 'customer'
     *   2. A role with slug 'viewer'
     *   3. The first non-system role in the org
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
                return; // Feature not configured — skip silently
            }

            $defaultOrgId = $flag->description;

            // Verify the org exists and is active
            $org = DB::connection('platform')
                ->table('organizations')
                ->where('id', $defaultOrgId)
                ->where('status', 'active')
                ->first();

            if (! $org) {
                return;
            }

            // Find the best-fit role: customer > viewer > first non-system role
            $role = OrgRole::where('root_org_id', $defaultOrgId)
                ->whereIn('slug', ['customer', 'viewer'])
                ->orderByRaw("CASE slug WHEN 'customer' THEN 0 WHEN 'viewer' THEN 1 END")
                ->first();

            if (! $role) {
                $role = OrgRole::where('root_org_id', $defaultOrgId)
                    ->where('is_system', false)
                    ->first();
            }

            if (! $role) {
                return; // No suitable role found — skip
            }

            // Avoid duplicate membership
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
                'status'      => 'active',
                'joined_at'   => now(),
            ]);

        } catch (\Throwable $e) {
            // Auto-assignment failure must never block registration
            \Illuminate\Support\Facades\Log::warning(
                "AuthService: auto-org-assignment failed for user {$user->id}: " . $e->getMessage()
            );
        }
    }

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