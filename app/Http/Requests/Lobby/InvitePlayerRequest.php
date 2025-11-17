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
            'user_id' => 'required|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'The specified user does not exist',
        ];
    }
}
