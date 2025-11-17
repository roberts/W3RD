<?php

namespace App\Http\Requests\Lobby;

use Illuminate\Foundation\Http\FormRequest;

class CreateLobbyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_title' => 'required|string',
            'game_mode' => 'nullable|string|in:standard,blitz,rapid',
            'is_public' => 'boolean',
            'min_players' => 'integer|min:2|max:8',
            'scheduled_at' => 'nullable|date|after:now',
            'invitees' => 'array',
            'invitees.*' => 'integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'game_title.required' => 'Game title is required',
            'game_mode.in' => 'Game mode must be one of: standard, blitz, rapid',
            'min_players.min' => 'Minimum players must be at least 2',
            'min_players.max' => 'Maximum players cannot exceed 8',
            'scheduled_at.after' => 'Scheduled time must be in the future',
            'invitees.*.exists' => 'One or more invited users do not exist',
        ];
    }
}
