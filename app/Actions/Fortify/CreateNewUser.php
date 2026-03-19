<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Modules\Platform\Contracts\Services\AuthServiceInterface;

class CreateNewUser implements CreatesNewUsers
{
    public function __construct(
        protected AuthServiceInterface $auth
    ) {}

    /**
     * Validate and create a new user via AuthService.
     * AuthService handles Actor creation, type assignment,
     * and tier assignment atomically in one transaction.
     */
    public function create(array $input): \App\Models\User
    {
        \Illuminate\Support\Facades\Validator::make($input, [
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:platform.users,username', 'regex:/^[a-zA-Z0-9_]+$/'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:platform.users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'username.regex' => 'Username may only contain letters, numbers, and underscores.',
        ])->validate();

        return $this->auth->register([
            'username' => $input['username'],
            'email'    => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
