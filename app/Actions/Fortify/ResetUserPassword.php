<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    public function reset(\App\Models\User $user, array $input): void
    {
        \Illuminate\Support\Facades\Validator::make($input, [
            'password' => ['required', 'confirmed', Password::defaults()],
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
