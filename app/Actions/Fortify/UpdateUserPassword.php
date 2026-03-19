<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    public function update(\App\Models\User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'current_password:web'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.current_password' => 'The provided password does not match your current password.',
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
