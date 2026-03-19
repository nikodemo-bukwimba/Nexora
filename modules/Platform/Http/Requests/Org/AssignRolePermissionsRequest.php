<?php

namespace Modules\Platform\Http\Requests\Org;

use Illuminate\Foundation\Http\FormRequest;

class AssignRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'permission_ids'   => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['string', 'size:26', 'exists:platform.org_permission_definitions,id'],
        ];
    }
}
