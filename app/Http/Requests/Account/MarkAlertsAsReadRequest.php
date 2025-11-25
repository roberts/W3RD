<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class MarkAlertsAsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'alert_ulids' => 'sometimes|array',
            'alert_ulids.*' => 'exists:alerts,ulid',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alert_ulids.array' => 'Alert ULIDs must be an array',
            'alert_ulids.*.exists' => 'One or more alert ULIDs do not exist',
        ];
    }
}
