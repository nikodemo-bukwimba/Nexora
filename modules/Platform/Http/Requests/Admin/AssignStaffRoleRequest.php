<?php

namespace Modules\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignStaffRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id'   => ['required', 'string', 'size:26'],
            'role_name' => ['required', 'string', 'exists:platform.platform_roles,name'],
        ];
    }
}
