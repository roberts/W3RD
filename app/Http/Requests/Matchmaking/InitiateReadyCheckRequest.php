<?php

namespace App\Http\Requests\Matchmaking;

use App\Matchmaking\Enums\LobbyStatus;
use App\Models\Matchmaking\Lobby;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InitiateReadyCheckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $lobby = $this->route('lobby');

        if (! $lobby instanceof Lobby) {
            return false;
        }

        // User must be the host
        if (! $lobby->isHost($this->user())) {
            return false;
        }

        // Lobby must be pending
        if ($lobby->status !== LobbyStatus::PENDING) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // No additional validation needed
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            //
        ];
    }
}
