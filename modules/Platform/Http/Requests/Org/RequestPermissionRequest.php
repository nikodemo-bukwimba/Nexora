<?php

namespace Modules\Platform\Http\Requests\Org;

use Illuminate\Foundation\Http\FormRequest;

class RequestPermissionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'org_role_id'           => ['required', 'string', 'size:26', 'exists:platform.org_roles,id'],
            'org_permission_def_id' => ['required', 'string', 'size:26', 'exists:platform.org_permission_definitions,id'],
            'reason'               => ['nullable', 'string', 'max:1000'],
        ];
    }
}
