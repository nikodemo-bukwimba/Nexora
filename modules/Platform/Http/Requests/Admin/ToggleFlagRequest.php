<?php

namespace Modules\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ToggleFlagRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'value' => ['required', 'boolean'],
        ];
    }
}
