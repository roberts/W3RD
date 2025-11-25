<?php

namespace App\Http\Requests\Matchmaking;

use Illuminate\Foundation\Http\FormRequest;

class RespondToInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => 'required|in:accepted,declined',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Response status is required',
            'status.in' => 'Status must be either "accepted" or "declined"',
        ];
    }
}
