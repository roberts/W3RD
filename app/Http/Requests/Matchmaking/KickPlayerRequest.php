<?php

namespace App\Http\Requests\Matchmaking;

use App\Models\Matchmaking\Lobby;
use Illuminate\Foundation\Http\FormRequest;

class KickPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lobby = $this->route('lobby');

        if ($lobby instanceof Lobby) {
            // Only the lobby host can kick players
            return $lobby->host_id === $this->user()->id;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // No body parameters needed - username comes from route
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            //
        ];
    }
}
