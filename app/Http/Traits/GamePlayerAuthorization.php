<?php

namespace App\Http\Traits;

use App\Enums\GameStatus;
use App\Exceptions\GameAccessDeniedException;
use App\Models\Game\Game;
use App\Models\Game\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

trait GamePlayerAuthorization
{
    /**
     * Authorize that the authenticated user is a player in the game.
     */
    protected function authorizeGamePlayer(Game $game): Player
    {
        /** @var Player|null $player */
        $player = $game->players()->where('user_id', Auth::id())->first();

        if (! $player) {
            throw new GameAccessDeniedException(
                'You are not a player in this game',
                $game->ulid,
                ['user_id' => Auth::id()]
            );
        }

        return $player;
    }

    /**
     * Verify that the game is in active status.
     */
    protected function authorizeActiveGame(Game $game): ?JsonResponse
    {
        if ($game->status !== GameStatus::ACTIVE) {
            return $this->errorResponse('This game is not active.', 400, 'invalid_game_state');
        }

        return null;
    }

    /**
     * Verify that it's the player's turn.
     */
    protected function authorizePlayerTurn(Player $player, string $currentPlayerUlid): ?JsonResponse
    {
        if ($currentPlayerUlid !== $player->ulid) {
            return $this->errorResponse('It is not your turn.', 400, 'invalid_turn');
        }

        return null;
    }
}
