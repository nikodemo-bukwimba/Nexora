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

            // 1. Create Actor
            $actor = $this->actors->create([
                'display_name' => $data['username'],
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

            // 5. Fire event
            $this->eventBus->fire(new UserRegistered($user), $actor->id);

            // 6. Audit
            $this->audit->log(
                module:      'platform',
                action:      'user.registered',
                subjectType: 'User',
                subjectId:   $user->id,
                newValues:   ['username' => $user->username, 'email' => $user->email],
                actorId:     $actor->id
            );

            return $user;
        });
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
