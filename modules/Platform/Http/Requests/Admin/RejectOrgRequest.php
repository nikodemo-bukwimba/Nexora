<?php

namespace Modules\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectOrgRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
