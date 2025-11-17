<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:registrations,email'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }
}
