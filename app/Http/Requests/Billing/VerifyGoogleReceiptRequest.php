<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class VerifyGoogleReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|string',
            'token' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product ID is required',
            'product_id.string' => 'Product ID must be a string',
            'token.required' => 'Token is required',
            'token.string' => 'Token must be a string',
        ];
    }
}
