<?php

namespace App\Agents\Logic;

use App\Interfaces\AgentContract;
use App\Models\Game\Game;
use Illuminate\Support\Facades\Log;

/**
 * RandomLogic - The simplest AI strategy
 *
 * This agent randomly selects from all valid actions without any
 * strategic consideration. Ideal for testing and as a baseline opponent.
 */
class RandomLogic implements AgentContract
{
    /**
     * Calculate the next action by randomly selecting from valid actions.
     *
     * @param  Game  $game  The current game instance
     * @param  int  $difficulty  The difficulty level (ignored for random strategy)
     * @return object An Action DTO representing the randomly selected action
     *
     * @throws \Exception If no valid actions are available
     */
    public function calculateNextAction(Game $game, int $difficulty): object
    {
        Log::debug('RandomLogic calculating action', [
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

        // Randomly select one of the valid actions
        $selectedAction = $validActions[array_rand($validActions)];

        Log::debug('RandomLogic selected action', [
            'game_id' => $game->id,
            'action_type' => get_class($selectedAction),
        ]);

        return $selectedAction;
    }

    /**
     * Get the game-specific logic handler based on the game title.
     *
     * @return object Game-specific logic handler
     *
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
