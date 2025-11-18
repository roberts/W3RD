<?php

namespace App\Agents\Logic;

use App\Interfaces\AgentContract;
use App\Models\Game\Game;
use Illuminate\Support\Facades\Log;

/**
 * MinimaxLogic - Strategic AI using minimax algorithm
 *
 * This agent uses the minimax algorithm with alpha-beta pruning to evaluate
 * the game tree and select optimal moves. The search depth increases with
 * difficulty level, making higher difficulty agents think further ahead.
 */
class MinimaxLogic implements AgentContract
{
    /**
     * Calculate the next action using minimax algorithm.
     *
     * @param Game $game The current game instance
     * @param int $difficulty The difficulty level (1-10) determines search depth
     * @return object An Action DTO representing the best calculated action
     *
     * @throws \Exception If no valid actions are available
     */
    public function calculateNextAction(Game $game, int $difficulty): object
    {
        Log::debug('MinimaxLogic calculating action', [
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

        // Calculate search depth based on difficulty (1-10 maps to 1-5 ply)
        $searchDepth = $this->calculateSearchDepth($difficulty);

        // Evaluate each action using minimax
        $bestAction = null;
        $bestScore = PHP_INT_MIN;

        foreach ($validActions as $action) {
            // Create a hypothetical game state after this action
            $futureState = $gameLogic->simulateAction($game, $action);

            // Evaluate this branch of the game tree
            $score = $this->minimax(
                $futureState,
                $searchDepth - 1,
                PHP_INT_MIN,
                PHP_INT_MAX,
                false, // Next turn is opponent (minimize)
                $gameLogic
            );

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestAction = $action;
            }
        }

        Log::debug('MinimaxLogic selected action', [
            'game_id' => $game->id,
            'best_score' => $bestScore,
            'search_depth' => $searchDepth,
        ]);

        return $bestAction ?? $validActions[array_rand($validActions)];
    }

    /**
     * Minimax algorithm with alpha-beta pruning.
     *
     * @param Game $gameState Current game state
     * @param int $depth Remaining search depth
     * @param int $alpha Alpha value for pruning
     * @param int $beta Beta value for pruning
     * @param bool $maximizing True if this is a maximizing node
     * @param object $gameLogic Game-specific logic handler
     * @return int Evaluated score for this game state
     */
    protected function minimax(
        Game $gameState,
        int $depth,
        int $alpha,
        int $beta,
        bool $maximizing,
        object $gameLogic
    ): int {
        // Base case: terminal node or max depth reached
        if ($depth === 0 || $gameLogic->isGameOver($gameState)) {
            return $gameLogic->evaluatePosition($gameState);
        }

        $validActions = $gameLogic->getValidActions($gameState);

        if ($maximizing) {
            $maxScore = PHP_INT_MIN;
            foreach ($validActions as $action) {
                $futureState = $gameLogic->simulateAction($gameState, $action);
                $score = $this->minimax($futureState, $depth - 1, $alpha, $beta, false, $gameLogic);
                $maxScore = max($maxScore, $score);
                $alpha = max($alpha, $score);
                if ($beta <= $alpha) {
                    break; // Beta cutoff
                }
            }
            return $maxScore;
        } else {
            $minScore = PHP_INT_MAX;
            foreach ($validActions as $action) {
                $futureState = $gameLogic->simulateAction($gameState, $action);
                $score = $this->minimax($futureState, $depth - 1, $alpha, $beta, true, $gameLogic);
                $minScore = min($minScore, $score);
                $beta = min($beta, $score);
                if ($beta <= $alpha) {
                    break; // Alpha cutoff
                }
            }
            return $minScore;
        }
    }

    /**
     * Calculate search depth based on difficulty level.
     *
     * @param int $difficulty 1-10 difficulty level
     * @return int Search depth in ply
     */
    protected function calculateSearchDepth(int $difficulty): int
    {
        // Map difficulty 1-10 to search depth 1-5
        // Difficulty 1-2: depth 1 (very shallow)
        // Difficulty 3-4: depth 2
        // Difficulty 5-6: depth 3
        // Difficulty 7-8: depth 4
        // Difficulty 9-10: depth 5 (deep search)
        return (int) ceil($difficulty / 2);
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
