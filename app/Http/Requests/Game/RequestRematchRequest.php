<?php

namespace App\Http\Requests\Game;

use App\Enums\GameStatus;
use App\Models\Games\Game;
use Illuminate\Foundation\Http\FormRequest;

class RequestRematchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $gameUlid = $this->route('gameUlid');
        $game = Game::where('ulid', $gameUlid)->first();

        if (! $game instanceof Game) {
            return false;
        }

        // User must be a player in the game
        $isPlayer = $game->getPlayerForUser($this->user()->id) !== null;

        if (! $isPlayer) {
            return false;
        }

        // Game must be completed
        if ($game->status !== GameStatus::COMPLETED) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
