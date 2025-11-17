<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'exists:registrations,verification_token'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.exists' => 'Invalid or expired verification token',
        ];
    }
}
