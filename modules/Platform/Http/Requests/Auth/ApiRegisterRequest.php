<?php

namespace Modules\Platform\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ApiRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Full name → stored as actor.display_name.
            // The Flutter client sends this as 'name'.
            // When absent, the username is used as display_name.
            'name'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'username'    => ['required', 'string', 'min:3', 'max:50', 'unique:platform.users,username', 'regex:/^[a-zA-Z0-9_]+$/'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:platform.users,email'],
            'password'    => ['required', 'confirmed', Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:100'],
            'org_id'   => ['sometimes', 'nullable', 'string', 'size:26'],
            'app_type' => ['sometimes', 'nullable', 'string', 'in:customer,officer'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username may only contain letters, numbers, and underscores.',
        ];
    }
}