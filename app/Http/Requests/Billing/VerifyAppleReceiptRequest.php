<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class VerifyAppleReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_id.required' => 'Transaction ID is required',
            'transaction_id.string' => 'Transaction ID must be a string',
        ];
    }
}
