<?php

namespace Modules\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignUserTierRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tier_name' => ['required', 'string', 'exists:platform.platform_tiers,name'],
        ];
    }
}
