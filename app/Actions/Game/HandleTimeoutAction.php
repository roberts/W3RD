<?php

namespace App\Actions\Game;

use App\DataTransferObjects\Game\TimeoutResult;
use App\Enums\GameStatus;
use App\Http\Traits\ApiResponses;
use App\Models\Game\Game;
use App\Models\Game\Player;
use App\Services\Timeouts\ForfeitHandler;
use App\Services\Timeouts\NoneHandler;
use App\Services\Timeouts\PassHandler;

class HandleTimeoutAction
{
    use ApiResponses;

    /**
     * Check if the current turn has timed out and handle accordingly.
     *
     * @param  mixed  $mode  The game mode handler
     */
    public function execute(Game $game, mixed $mode, mixed $gameState): TimeoutResult
    {
        $deadline = $mode->getActionDeadline($gameState, $game);

        if (now()->isBefore($deadline)) {
            return TimeoutResult::noTimeout();
        }

        $penalty = $mode->getTimeoutPenalty();

        // Get timeout handler
        $timeoutHandler = match ($penalty) {
            'forfeit' => new ForfeitHandler,
            'pass' => new PassHandler,
            'none' => new NoneHandler,
            default => new NoneHandler,
        };

        $outcome = $timeoutHandler->handleTimeout($game, $gameState, $gameState->currentPlayerUlid);

        if ($outcome->isFinished) {
            $game->status = GameStatus::COMPLETED;
            $game->outcome_type = $outcome->type;
            $game->outcome_details = $outcome->details;
            $game->completed_at = now();

            if ($outcome->winnerUlid) {
                /** @var Player $winner */
                $winner = $game->players()->where('ulid', $outcome->winnerUlid)->first();
                $game->winner_id = $winner->id;
                $game->winner_position = $outcome->winnerPosition;
            }

            $game->save();

            return TimeoutResult::timeout(
                $this->errorResponse(
                    'Your turn has timed out. You have forfeited the game.',
                    408,
                    'action_timeout',
                    ['game_status' => 'completed', 'penalty' => $penalty]
                )
            );
        }

        // Pass strategy - advance to next player
        if ($penalty === 'pass') {
            $gameState = $gameState->withNextPlayer();
            $game->game_state = $gameState->toArray();
            $game->save();

            return TimeoutResult::timeout(
                $this->errorResponse(
                    'Your turn has timed out and has been passed.',
                    408,
                    'action_timeout',
                    ['penalty' => 'pass']
                )
            );
        }

        return TimeoutResult::noTimeout();
    }
}
