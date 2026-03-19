<?php

namespace Modules\Platform\Http\Requests\Org;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrgRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'string', 'min:2', 'max:255'],
            'settings' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
