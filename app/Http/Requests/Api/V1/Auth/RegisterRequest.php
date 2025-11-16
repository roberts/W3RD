<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'ulid', 'exists:clients,id'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:registrations,email'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }
}
