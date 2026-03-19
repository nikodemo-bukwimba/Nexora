<?php

namespace Modules\Platform\Http\Requests\Org;

use Illuminate\Foundation\Http\FormRequest;

class DelegateRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'child_org_id'    => ['required', 'string', 'size:26', 'exists:platform.organizations,id'],
            'org_role_id'     => ['required', 'string', 'size:26', 'exists:platform.org_roles,id'],
            'permission_ids'   => ['nullable', 'array'],
            'permission_ids.*' => ['string', 'size:26', 'exists:platform.org_permission_definitions,id'],
        ];
    }
}
