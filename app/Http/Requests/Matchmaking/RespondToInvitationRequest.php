<?php

namespace App\Http\Requests\Matchmaking;

use Illuminate\Foundation\Http\FormRequest;

class RespondToInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:accepted,declined',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Response status is required',
            'status.in' => 'Status must be either "accepted" or "declined"',
        ];
    }
}
