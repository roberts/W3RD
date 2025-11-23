<?php

declare(strict_types=1);

namespace App\GameEngine\Lifecycle\Conclusion;

use App\Enums\OutcomeType;
use App\GameEngine\GameOutcome;
use App\Models\Games\Game;

/**
 * Evaluates game outcomes and determines winners, rankings, and scores.
 */
class OutcomeEvaluator
{
    /**
     * Evaluate the final outcome of a game.
     *
     * @param  Game  $game  The completed game
     * @param  GameOutcome  $outcome  The outcome from the arbiter
     * @return array Detailed outcome information
     */
    public function evaluate(Game $game, GameOutcome $outcome): array
    {
        return [
            'outcome_type' => $outcome->type,
            'winner_ulid' => $outcome->winnerUlid,
            'is_draw' => $outcome->type === OutcomeType::DRAW,
            'rankings' => $outcome->details['rankings'] ?? [],
            'scores' => $outcome->details['scores'] ?? [],
            'reason' => $outcome->details['reason'] ?? null,
            'statistics' => $this->calculateStatistics($game, $outcome),
        ];
    }

    /**
     * Calculate game statistics.
     *
     * @param  Game  $game  The game instance
     * @param  GameOutcome  $outcome  The game outcome
     * @return array Statistical data
     */
    protected function calculateStatistics(Game $game, GameOutcome $outcome): array
    {
        return [
            'total_turns' => $game->turn_number,
            'duration_seconds' => $game->started_at?->diffInSeconds($game->completed_at),
            'total_actions' => $game->actions()->count(),
            'player_count' => $game->players()->count(),
        ];
    }

    /**
     * Determine if a game should end based on current state.
     *
     * @param  mixed  $arbiter  The game arbiter
     * @param  object  $gameState  The current game state
     * @return GameOutcome|null Outcome if game should end, null otherwise
     */
    public function checkEndCondition(mixed $arbiter, object $gameState): ?GameOutcome
    {
        if (method_exists($arbiter, 'determineOutcome')) {
            return $arbiter->determineOutcome($gameState);
        }

        return null;
    }
}
