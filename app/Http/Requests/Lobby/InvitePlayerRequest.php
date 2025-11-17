<?php

namespace App\Http\Requests\Lobby;

use Illuminate\Foundation\Http\FormRequest;

class InvitePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string|exists:users,username',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username is required',
            'username.exists' => 'The specified user does not exist',
        ];
    }
}
