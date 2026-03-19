<?php

namespace Modules\Platform\Http\Requests\Org;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrgRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'            => ['sometimes', 'string', 'max:100'],
            'permission_ids'  => ['nullable', 'array'],
            'permission_ids.*'=> ['string', 'size:26', 'exists:platform.org_permission_definitions,id'],
        ];
    }
}
