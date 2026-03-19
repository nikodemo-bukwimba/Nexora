<?php

namespace Modules\Platform\Http\Requests\Org;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrgRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:100'],
            'default_role_id' => ['nullable', 'string', 'size:26', 'exists:platform.platform_default_roles,id'],
        ];
    }
}
