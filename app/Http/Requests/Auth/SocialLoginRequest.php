<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SocialLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'in:google,telegram,github'],
            'access_token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'provider.in' => 'Provider must be one of: google, telegram, github',
        ];
    }
}
