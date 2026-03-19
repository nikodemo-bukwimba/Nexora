<?php

namespace Modules\Platform\Contracts\Services;

use Modules\Platform\Models\User;

interface AuthServiceInterface
{
    /**
     * Register a new user and their associated Actor in a single transaction.
     * Assigns the 'user' actor type and default platform tier automatically.
     */
    public function register(array $data): User;

    /**
     * Attempt login and return a Sanctum token string.
     * Returns null if credentials are invalid or user is suspended.
     */
    public function loginWithToken(string $email, string $password, string $deviceName): ?string;

    /**
     * Revoke the currently authenticated user's token.
     */
    public function revokeToken(User $user): void;

    /**
     * Update the user's last login timestamp and IP.
     */
    public function recordLogin(User $user, string $ip): void;
}
