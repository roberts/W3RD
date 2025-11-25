<?php

declare(strict_types=1);

namespace App\GameEngine\Lifecycle\Progression;

use App\DataTransferObjects\Games\CoordinatedActionResult;
use App\GameEngine\Interfaces\GameActionContract;
use App\Models\Games\Action;
use App\Models\Games\Game;

/**
 * Handles coordinated actions where multiple players must submit actions
 * before the game state can progress (e.g., simultaneous card passing in Hearts).
 */
class CoordinatedActionProcessor
{
    /**
     * Process a potentially coordinated action.
     *
     * @param  Game  $game  The game instance
     * @param  GameActionContract  $action  The action being processed
     * @param  mixed  $mode  The game mode handler
     * @param  object  $gameState  The current game state
     * @return CoordinatedActionResult Result indicating if coordination is needed and status
     */
    public function process(Game $game, GameActionContract $action, mixed $mode, object $gameState): CoordinatedActionResult
    {
        // Check if this action requires coordination
        if (! $this->isCoordinatedAction($action)) {
            return CoordinatedActionResult::notCoordinated();
        }

        $coordinationGroup = $this->buildCoordinationGroup($game, $action, $gameState);
        $coordinationSequence = $this->getSequenceNumber($game, $coordinationGroup);
        $requiredCount = $this->getRequiredPlayerCount($game, $action, $gameState);

        // Check if coordination is complete
        $completedCount = $this->getCompletedCount($game, $coordinationGroup);

        if ($completedCount >= $requiredCount) {
            return $this->completeCoordination($game, $mode, $gameState, $coordinationGroup, $coordinationSequence);
        }

        return CoordinatedActionResult::coordinated(
            coordinationGroup: $coordinationGroup,
            coordinationSequence: $coordinationSequence,
            coordinationComplete: false
        );
    }

    /**
     * Determine if an action requires coordination.
     */
    protected function isCoordinatedAction(GameActionContract $action): bool
    {
        // Actions that require coordination from multiple players
        return in_array($action->getType(), [
            'pass_cards',
            // Future coordinated actions can be added here
        ]);
    }

    /**
     * Build the coordination group identifier.
     */
    protected function buildCoordinationGroup(Game $game, GameActionContract $action, object $gameState): string
    {
        return match ($action->getType()) {
            'pass_cards' => $this->buildPassCardsGroup($game, $gameState),
            default => "game:{$game->id}:coordinated:{$action->getType()}",
        };
    }

    /**
     * Build coordination group for Hearts card passing.
     */
    protected function buildPassCardsGroup(Game $game, object $gameState): string
    {
        $roundNumber = $gameState->roundNumber ?? 1;

        return "game:{$game->id}:pass:round:{$roundNumber}";
    }

    /**
     * Get the current sequence number for this coordination group.
     */
    protected function getSequenceNumber(Game $game, string $coordinationGroup): int
    {
        return Action::where('game_id', $game->id)
            ->withCoordinationGroup($coordinationGroup)
            ->count() + 1;
    }

    /**
     * Get the number of players required to complete coordination.
     */
    protected function getRequiredPlayerCount(Game $game, GameActionContract $action, object $gameState): int
    {
        // For most coordinated actions, all players must participate
        return match ($action->getType()) {
            'pass_cards' => 4, // Hearts requires all 4 players
            default => $game->players()->count(),
        };
    }

    /**
     * Get the count of completed actions in this coordination group.
     */
    protected function getCompletedCount(Game $game, string $coordinationGroup): int
    {
        return Action::where('game_id', $game->id)
            ->withCoordinationGroup($coordinationGroup)
            ->pendingCoordination()
            ->count();
    }

    /**
     * Complete the coordinated action when all players have submitted.
     */
    protected function completeCoordination(
        Game $game,
        mixed $mode,
        object $gameState,
        string $coordinationGroup,
        int $coordinationSequence
    ): CoordinatedActionResult {
        // Retrieve all coordinated actions
        $coordinatedActions = Action::where('game_id', $game->id)
            ->withCoordinationGroup($coordinationGroup)
            ->pendingCoordination()
            ->with('player')
            ->get();

        // Process the coordinated action through the game mode
        $updatedGameState = $this->processCoordinatedActions($mode, $gameState, $coordinatedActions);

        // Mark all actions as completed
        Action::where('game_id', $game->id)
            ->withCoordinationGroup($coordinationGroup)
            ->pendingCoordination()
            ->update(['coordination_completed_at' => now()]);

        return CoordinatedActionResult::coordinated(
            coordinationGroup: $coordinationGroup,
            coordinationSequence: $coordinationSequence,
            coordinationComplete: true,
            updatedGameState: $updatedGameState
        );
    }

    /**
     * Process the coordinated actions through the appropriate game mode handler.
     *
     * @param  iterable<int, mixed>  $coordinatedActions
     */
    protected function processCoordinatedActions(mixed $mode, object $gameState, $coordinatedActions): object
    {
        // Delegate to the game mode's specific coordination handler
        if (method_exists($mode, 'processPassCards')) {
            return $mode->processPassCards($gameState, $coordinatedActions);
        }

        // If no specific handler exists, return state unchanged
        return $gameState;
    }
}
