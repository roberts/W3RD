<?php

namespace App\Http\Requests\Games;

use App\Enums\GameErrorCode;
use App\Enums\GameStatus;
use App\Exceptions\GameActionDeniedException;
use App\Models\Games\Game;
use App\Models\Games\Player;
use Illuminate\Foundation\Http\FormRequest;

abstract class BaseGameRequest extends FormRequest
{
    protected ?Game $game = null;

    protected ?Player $player = null;

    /**
     * Get the game from route parameter and authorize player access.
     */
    protected function authorizeGameAccess(string $paramName = 'gameUlid'): bool
    {
        $gameUlid = $this->route($paramName);

        if (! $gameUlid) {
            return false;
        }

        try {
            $this->game = Game::withUlid($gameUlid, ['players', 'mode'])->firstOrFail();
        } catch (\Exception $e) {
            return false;
        }

        $this->player = $this->game->getPlayerForUser($this->user()?->id);

        return $this->player !== null;
    }

    /**
     * Verify that the game is in active status.
     * Call this from authorize() after authorizeGameAccess().
     */
    protected function authorizeActiveGame(): bool
    {
        if (! $this->game) {
            throw new \LogicException('Call authorizeGameAccess() before authorizeActiveGame()');
        }

        if ($this->game->status !== GameStatus::ACTIVE) {
            $context = [
                'game_status' => $this->game->status->value,
                'completed_at' => $this->game->completed_at?->toIso8601String(),
            ];

            // Add winner information if game is completed
            if ($this->game->status === GameStatus::COMPLETED && $this->game->winner_id) {
                /** @var Player|null $winner */
                $winner = $this->game->players()->where('id', $this->game->winner_id)->first();
                if ($winner) {
                    $context['winner'] = [
                        'player_ulid' => $winner->ulid,
                        'player_username' => $winner->user->username,
                        'player_position' => $winner->position_id,
                    ];
                }
            }

            // Add abandonment reason if available
            if ($this->game->status === GameStatus::ABANDONED) {
                $context['reason'] = 'Game was abandoned by players';
            }

            throw new GameActionDeniedException(
                'This game is not active.',
                GameErrorCode::GAME_ALREADY_COMPLETED->value,
                $this->game->title_slug->value,
                'error',
                $context
            );
        }

        return true;
    }

    /**
     * Get the authorized player (call after authorize() passes).
     */
    public function player(): Player
    {
        if (! $this->player) {
            throw new \LogicException('Player not loaded. Ensure authorize() calls authorizeGameAccess().');
        }

        return $this->player;
    }

    /**
     * Get the game (call after authorize() passes).
     */
    public function game(): Game
    {
        if (! $this->game) {
            throw new \LogicException('Game not loaded. Ensure authorize() calls authorizeGameAccess().');
        }

        return $this->game;
    }
}
