<?php

namespace App\Http\Requests\Economy;

use Illuminate\Foundation\Http\FormRequest;

class VerifyReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receipt' => ['required', 'string'],
        ];
    }
}
