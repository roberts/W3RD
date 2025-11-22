<?php

namespace App\Actions\Game;

use App\DataTransferObjects\Game\TimeoutResult;
use App\Enums\GameStatus;
use App\GameEngine\GameOutcome;
use App\GameEngine\TimerExpired\TimerExpiredManager;
use App\Http\Traits\ApiResponses;
use App\Models\Game\Game;
use App\Models\Game\Player;

class HandleTimeoutAction
{
    use ApiResponses;

    public function __construct(private TimerExpiredManager $timerExpiredManager)
    {
    }

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

        $timer = $mode->getTimer();
        $driver = $this->timerExpiredManager->getDriverFor($timer);

        /** @var Player|null $player */
        $player = $game->players()->where('id', $game->current_player_id)->first();

        if (!$player) {
            return TimeoutResult::noTimeout();
        }

        $outcome = $driver->handleTimerExpired($game, $gameState, $player->ulid);

        if ($outcome->isFinished) {
            $game->status = GameStatus::COMPLETED;
            $game->outcome_type = $outcome->type;
            $game->outcome_details = $outcome->details;
            $game->completed_at = now();

            if ($outcome->winnerUlid) {
                /** @var Player $winner */
                $winner = $game->players()->where('ulid', $outcome->winnerUlid)->first();
                $game->winner_id = $winner->id;
            }

            $game->save();

            return TimeoutResult::timeout(
                $this->errorResponse(
                    'Your turn has timed out. You have forfeited the game.',
                    408,
                    'action_timeout',
                    ['game_status' => 'completed', 'penalty' => $driver->getName()]
                )
            );
        }

        // The driver should have updated the game state if the penalty was 'pass'
        $game->game_state = $outcome->getGameState();
        $game->save();

        return TimeoutResult::timeout(
            $this->errorResponse(
                'Your turn has timed out and has been passed.',
                408,
                'action_timeout',
                ['penalty' => $driver->getName()]
            )
        );
    }
}
