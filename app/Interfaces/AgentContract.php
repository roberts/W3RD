<?php

namespace App\Interfaces;

use App\Models\Game\Game;

/**
 * Interface for all AI agent logic implementations.
 *
 * Each agent AI strategy (Random, Minimax, Heuristic, etc.) must implement
 * this interface to ensure consistent behavior across the system.
 */
interface AgentContract
{
    /**
     * Calculate the next action for the agent in the given game.
     *
     * @param  Game  $game  The current game instance
     * @param  int  $difficulty  The difficulty level (1-10) for this agent's turn
     * @return object An Action DTO (e.g., MoveAction, PlayCardAction) representing the agent's decision
     *
     * @throws \Exception If the agent cannot calculate a valid action
     */
    public function calculateNextAction(Game $game, int $difficulty): object;
}
