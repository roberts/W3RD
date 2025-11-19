<?php

namespace App\Http\Traits;

use App\Enums\BaseGameActionErrorCode;
use App\Enums\GameStatus;
use App\Exceptions\GameAccessDeniedException;
use App\Exceptions\GameActionDeniedException;
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
     *
     * @throws GameActionDeniedException
     */
    protected function authorizeActiveGame(Game $game): ?JsonResponse
    {
        if ($game->status !== GameStatus::ACTIVE) {
            $context = [
                'game_status' => $game->status->value,
                'finished_at' => $game->finished_at?->toIso8601String(),
            ];

            // Add winner information if game is completed
            if ($game->status === GameStatus::COMPLETED && $game->winner_id) {
                /** @var Player|null $winner */
                $winner = $game->players()->where('id', $game->winner_id)->first();
                if ($winner) {
                    $context['winner'] = [
                        'player_ulid' => $winner->ulid,
                        'player_username' => $winner->user->username,
                        'player_position' => $winner->position_id,
                    ];
                }
            }

            // Add abandonment reason if available
            if ($game->status === GameStatus::ABANDONED) {
                $context['reason'] = 'Game was abandoned by players';
            }

            throw new GameActionDeniedException(
                'This game is not active.',
                BaseGameActionErrorCode::GAME_ALREADY_COMPLETED->value,
                $game->title_slug->value,
                'error',
                $context
            );
        }

        return null;
    }

    /**
     * Verify that it's the player's turn.
     *
     * @throws GameActionDeniedException
     */
    protected function authorizePlayerTurn(Player $player, string $currentPlayerUlid): ?JsonResponse
    {
        if ($currentPlayerUlid !== $player->ulid) {
            // Find the current player to get their username and position
            /** @var Player|null $currentPlayer */
            $currentPlayer = $player->game->players()->where('ulid', $currentPlayerUlid)->first();
            
            throw new GameActionDeniedException(
                'It is not your turn.',
                BaseGameActionErrorCode::NOT_PLAYER_TURN->value,
                $player->game->title_slug->value,
                'error',
                [
                    'current_turn' => [
                        'player_ulid' => $currentPlayerUlid,
                        'player_username' => $currentPlayer?->user->username ?? 'Unknown',
                        'player_position' => $currentPlayer?->position_id,
                    ],
                    'your_info' => [
                        'player_ulid' => $player->ulid,
                        'player_username' => $player->user->username,
                        'player_position' => $player->position_id,
                    ],
                    'turn_number' => $player->game->turn_number ?? 1,
                ]
            );
        }

        return null;
    }
}
