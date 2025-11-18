<?php

namespace App\Agents\Logic;

use App\Interfaces\AgentContract;
use App\Models\Game\Game;
use Illuminate\Support\Facades\Log;

/**
 * HeuristicLogic - Pattern-based AI strategy
 *
 * This agent uses heuristic evaluation functions to score moves without
 * deep tree search. It recognizes common patterns, piece values, and
 * positional advantages. Faster than minimax but less optimal.
 */
class HeuristicLogic implements AgentContract
{
    /**
     * Calculate the next action using heuristic evaluation.
     *
     * @param Game $game The current game instance
     * @param int $difficulty The difficulty level (1-10) affects evaluation weights
     * @return object An Action DTO representing the best heuristically-evaluated action
     *
     * @throws \Exception If no valid actions are available
     */
    public function calculateNextAction(Game $game, int $difficulty): object
    {
        Log::debug('HeuristicLogic calculating action', [
            'game_id' => $game->id,
            'game_title' => $game->title_slug->value ?? 'unknown',
            'difficulty' => $difficulty,
        ]);

        // Get the game-specific logic handler
        $gameLogic = $this->getGameLogic($game);

        // Get all valid actions for the current game state
        $validActions = $gameLogic->getValidActions($game);

        if (empty($validActions)) {
            throw new \Exception('No valid actions available for agent to perform');
        }

        // Evaluate each action using game-specific heuristics
        $bestAction = null;
        $bestScore = PHP_INT_MIN;

        foreach ($validActions as $action) {
            // Calculate heuristic score for this action
            $score = $this->evaluateAction($game, $action, $difficulty, $gameLogic);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestAction = $action;
            }
        }

        Log::debug('HeuristicLogic selected action', [
            'game_id' => $game->id,
            'best_score' => $bestScore,
            'difficulty' => $difficulty,
        ]);

        return $bestAction ?? $validActions[array_rand($validActions)];
    }

    /**
     * Evaluate an action using heuristic scoring.
     *
     * @param Game $game Current game state
     * @param object $action The action to evaluate
     * @param int $difficulty Affects evaluation sophistication
     * @param object $gameLogic Game-specific logic handler
     * @return float Heuristic score for this action
     */
    protected function evaluateAction(Game $game, object $action, int $difficulty, object $gameLogic): float
    {
        // Simulate the action to get the resulting game state
        $futureState = $gameLogic->simulateAction($game, $action);

        $score = 0.0;

        // Material/piece value (weighted by difficulty)
        $materialWeight = $difficulty / 10; // 0.1 to 1.0
        $score += $gameLogic->calculateMaterialAdvantage($futureState) * $materialWeight;

        // Positional advantage (more important at higher difficulty)
        if ($difficulty >= 4) {
            $positionalWeight = ($difficulty - 3) / 7; // 0.14 to 1.0 for difficulty 4-10
            $score += $gameLogic->calculatePositionalAdvantage($futureState) * $positionalWeight;
        }

        // Mobility/options (how many moves will be available)
        if ($difficulty >= 6) {
            $mobilityWeight = ($difficulty - 5) / 5; // 0.2 to 1.0 for difficulty 6-10
            $futureValidActions = $gameLogic->getValidActions($futureState);
            $score += count($futureValidActions) * $mobilityWeight;
        }

        // Threat detection (can opponent capture valuable pieces?)
        if ($difficulty >= 8) {
            $threatPenalty = $gameLogic->calculateThreatLevel($futureState);
            $score -= $threatPenalty * 2; // Threats are bad
        }

        // Add small random variation to prevent predictability
        $randomFactor = ($difficulty < 10) ? rand(-5, 5) / 10 : 0;
        $score += $randomFactor;

        return $score;
    }

    /**
     * Get the game-specific logic handler based on the game title.
     *
     * @param Game $game
     * @return object Game-specific logic handler
     * @throws \Exception If game type is not supported
     */
    protected function getGameLogic(Game $game): object
    {
        $gameTitle = $game->title_slug->value ?? null;

        return match ($gameTitle) {
            // @phpstan-ignore class.notFound
            'checkers' => app(\App\Games\Checkers\CheckersLogic::class),
            // @phpstan-ignore class.notFound
            'hearts' => app(\App\Games\Hearts\HeartsLogic::class),
            // @phpstan-ignore class.notFound, match.alwaysFalse
            'validatefour' => app(\App\Games\ValidateFour\ValidateFourLogic::class),
            default => throw new \Exception("Unsupported game type: {$gameTitle}"),
        };
    }
}
