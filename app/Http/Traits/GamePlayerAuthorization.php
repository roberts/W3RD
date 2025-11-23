<?php

namespace App\Http\Traits;

use App\Enums\GameErrorCode;
use App\Exceptions\GameActionDeniedException;
use App\Models\Games\Player;
use Illuminate\Http\JsonResponse;

/**
 * Game player authorization trait for edge cases.
 * 
 * For most authorization needs, use BaseGameRequest instead.
 * This trait is kept for special scenarios like turn validation
 * or when authorization needs to happen mid-controller logic.
 */
trait GamePlayerAuthorization
{
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
                GameErrorCode::NOT_PLAYER_TURN->message(),
                GameErrorCode::NOT_PLAYER_TURN->value,
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
