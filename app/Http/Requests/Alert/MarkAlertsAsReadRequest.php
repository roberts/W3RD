<?php

namespace App\Http\Requests\Alert;

use Illuminate\Foundation\Http\FormRequest;

class MarkAlertsAsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alert_ids' => 'sometimes|array',
            'alert_ids.*' => 'exists:alerts,id',
        ];
    }

    public function messages(): array
    {
        return [
            'alert_ids.array' => 'Alert IDs must be an array',
            'alert_ids.*.exists' => 'One or more alert IDs do not exist',
        ];
    }
}
