<?php

namespace Modules\Platform\Http\Requests\Org;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'org_role_id' => ['sometimes', 'string', 'size:26', 'exists:platform.org_roles,id'],
            'level'       => ['sometimes', 'integer', 'min:0', 'max:100'],
            'status'      => ['sometimes', 'string', 'in:active,suspended'],
        ];
    }
}
