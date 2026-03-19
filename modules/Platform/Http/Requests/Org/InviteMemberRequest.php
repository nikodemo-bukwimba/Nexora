<?php

namespace Modules\Platform\Http\Requests\Org;

use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'string', 'email', 'max:255'],
            'org_role_id' => ['required', 'string', 'size:26', 'exists:platform.org_roles,id'],
            'level'       => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
