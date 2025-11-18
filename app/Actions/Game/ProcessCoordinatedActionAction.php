<?php

namespace App\Actions\Game;

use App\DataTransferObjects\Game\CoordinatedActionResult;
use App\Models\Game\Action;
use App\Models\Game\Game;

class ProcessCoordinatedActionAction
{
    /**
     * Process coordinated actions (e.g., Hearts pass_cards).
     *
     * @param  object  $action  The action DTO
     * @param  mixed  $mode  The game mode handler
     */
    public function execute(Game $game, object $action, mixed $mode, mixed $gameState): CoordinatedActionResult
    {
        // Check if this is a coordinated action
        if ($action->getType() !== 'pass_cards') {
            return CoordinatedActionResult::notCoordinated();
        }

        // Build coordination group for Hearts card passing
        $roundNumber = $gameState->roundNumber ?? 1;
        $coordinationGroup = "game:{$game->id}:pass:round:{$roundNumber}";

        // Get current sequence number (how many have submitted so far, before this one)
        $coordinationSequence = Action::where('game_id', $game->id)
            ->where('coordination_group', $coordinationGroup)
            ->count() + 1;

        // Check if coordination is complete
        $completedCount = Action::where('game_id', $game->id)
            ->where('coordination_group', $coordinationGroup)
            ->whereNull('coordination_completed_at')
            ->count();

        // For Hearts pass_cards, we need all 4 players
        $requiredCount = 4;

        if ($completedCount >= $requiredCount) {
            // All players have submitted - process the coordination
            $passActions = Action::where('game_id', $game->id)
                ->where('coordination_group', $coordinationGroup)
                ->whereNull('coordination_completed_at')
                ->with('player')
                ->get();

            // Process the coordinated action
            if (method_exists($mode, 'processPassCards')) {
                $updatedGameState = $mode->processPassCards($gameState, $passActions);

                // Mark all pass actions as completed
                Action::where('game_id', $game->id)
                    ->where('coordination_group', $coordinationGroup)
                    ->whereNull('coordination_completed_at')
                    ->update(['coordination_completed_at' => now()]);

                return CoordinatedActionResult::coordinated(
                    coordinationGroup: $coordinationGroup,
                    coordinationSequence: $coordinationSequence,
                    coordinationComplete: true,
                    updatedGameState: $updatedGameState
                );
            }
        }

        return CoordinatedActionResult::coordinated(
            coordinationGroup: $coordinationGroup,
            coordinationSequence: $coordinationSequence,
            coordinationComplete: false
        );
    }
}
