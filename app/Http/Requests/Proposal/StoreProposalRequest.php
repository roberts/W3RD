<?php

namespace App\Http\Requests\Proposal;

use App\Enums\GameStatus;
use App\Models\Game\Game;
use Illuminate\Foundation\Http\FormRequest;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $type = $this->input('type') ?? 'rematch';

        if ($type !== 'rematch') {
            return true;
        }

        $gameUlid = $this->input('original_game_ulid');

        if (! $gameUlid) {
            // Let validation handle missing/invalid values
            return true;
        }

        /** @var Game|null $game */
        $game = Game::where('ulid', $gameUlid)->first();

        if (! $game) {
            return true;
        }

        if ($game->status !== GameStatus::COMPLETED) {
            return false;
        }

        return $game->players()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|string|in:rematch,casual,tournament',
            'opponent_username' => 'required_unless:type,rematch|string',
            'title_slug' => 'nullable|string',
            'mode_id' => 'nullable|integer|exists:game_modes,id',
            'original_game_ulid' => 'required_if:type,rematch|string|exists:games,ulid',
            'game_settings' => 'nullable|array',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->input('type', 'rematch'),
        ]);
    }
}
