<?php

namespace App\Http\Requests\Economy;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTelegramReceiptRequest extends FormRequest
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
            'data' => 'required|array',
            'hash' => 'required|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'data.required' => 'Payment data is required',
            'data.array' => 'Payment data must be an array',
            'hash.required' => 'Payment hash is required',
            'hash.string' => 'Payment hash must be a string',
        ];
    }
}
