<?php

namespace App\Http\Requests\Matchmaking;

use App\Models\Matchmaking\Lobby;
use Illuminate\Foundation\Http\FormRequest;

class KickPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lobbyUlid = $this->route('lobby_ulid');

        if (is_string($lobbyUlid)) {
            $lobby = Lobby::withUlid($lobbyUlid)->firstOrFail();

            // Only the lobby host can kick players
            return $lobby->host_id === $this->user()->id;
        }

        return false;
    }

    public function rules(): array
    {
        return [
            // No body parameters needed - username comes from route
        ];
    }

    public function messages(): array
    {
        return [
            //
        ];
    }
}
