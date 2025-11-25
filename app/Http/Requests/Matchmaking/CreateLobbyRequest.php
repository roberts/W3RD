<?php

namespace App\Http\Requests\Matchmaking;

use Illuminate\Foundation\Http\FormRequest;

class CreateLobbyRequest extends FormRequest
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
            'game_title' => 'required|string',
            'mode_id' => 'required|integer|exists:modes,id',
            'is_public' => 'boolean',
            'min_players' => 'integer|min:2|max:8',
            'scheduled_at' => 'nullable|date|after:now',
            'invitees' => 'array',
            'invitees.*' => 'integer|exists:users,id',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'game_title.required' => 'Game title is required',
            'mode_id.required' => 'Mode is required',
            'mode_id.exists' => 'Invalid mode selected',
            'min_players.min' => 'Minimum players must be at least 2',
            'min_players.max' => 'Maximum players cannot exceed 8',
            'scheduled_at.after' => 'Scheduled time must be in the future',
            'invitees.*.exists' => 'One or more invited users do not exist',
        ];
    }
}
