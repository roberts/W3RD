<?php

declare(strict_types=1);

namespace App\GameEngine\Timers;

use App\Enums\GameAttributes\GameTimer;
use App\Exceptions\Game\TimerNotAvailableException;
use App\GameEngine\Interfaces\GameTitleContract;
use App\Models\Games\Game;
use Carbon\Carbon;

/**
 * Provides timer information for games with time limits.
 */
class TimerInformationService
{
    /**
     * Get comprehensive timer information for the current turn.
     *
     * @throws TimerNotAvailableException if the game does not use a timer
     */
    public function getTimerInfo(Game $game, GameTitleContract $mode, object $gameState): array
    {
        // Check if game has a timer
        if (! $this->hasTimer($mode)) {
            throw TimerNotAvailableException::noTimer();
        }

        $timelimit = $mode->getTimelimit();
        $deadline = $mode->getActionDeadline($gameState, $game);

        $timerInfo = [
            'turn_number' => $gameState->turnNumber ?? $game->turn_number ?? 1,
            'limit_seconds' => $timelimit,
            'deadline' => $deadline->toIso8601String(),
            'remaining_seconds' => max(0, $deadline->diffInSeconds(now(), false)),
            'penalty' => $mode->getTimeoutPenalty(),
        ];

        // Add turn start time if available in game state
        if (isset($gameState->turnStartedAt)) {
            $turnStarted = Carbon::parse($gameState->turnStartedAt);
            $timerInfo['started_at'] = $turnStarted->toIso8601String();
            $timerInfo['elapsed_seconds'] = $turnStarted->diffInSeconds(now());
        }

        return $timerInfo;
    }

    /**
     * Check if a game mode has a timer.
     */
    public function hasTimer(GameTitleContract $mode): bool
    {
        $penalty = $mode->getTimeoutPenalty();

        return $penalty !== GameTimer::NONE->value;
    }

    /**
     * Get basic timer configuration without state-dependent information.
     *
     * @throws TimerNotAvailableException if the game does not use a timer
     */
    public function getTimerConfig(GameTitleContract $mode): array
    {
        if (! $this->hasTimer($mode)) {
            throw TimerNotAvailableException::noTimer();
        }

        return [
            'limit_seconds' => $mode->getTimelimit(),
            'penalty' => $mode->getTimeoutPenalty(),
        ];
    }
}
