<?php

declare(strict_types=1);

namespace App\GameEngine\Timeline;

use App\GameEngine\Interfaces\GameActionContract;
use App\GameEngine\ValidationResult;
use App\Models\Game\Action;
use App\Models\Game\Game;
use App\Models\Game\Player;

/**
 * Service for recording game actions to the database.
 *
 * Centralizes action logging logic that was previously embedded in the controller.
 * Records both successful and failed actions for debugging and replay capabilities.
 */
class ActionRecorder
{
    /**
     * Record a game action to the database.
     *
     * @param  Game  $game  The game instance
     * @param  Player  $player  The player who performed the action
     * @param  GameActionContract  $action  The action that was performed
     * @param  ValidationResult  $validationResult  The validation result
     * @param  int  $turnNumber  The current turn number
     * @param  string|null  $coordinationGroup  Optional coordination group identifier
     * @param  int|null  $coordinationSequence  Optional sequence within coordination group
     * @return Action The created action
     */
    public function record(
        Game $game,
        Player $player,
        GameActionContract $action,
        ValidationResult $validationResult,
        int $turnNumber,
        ?string $coordinationGroup = null,
        ?int $coordinationSequence = null,
    ): Action {
        $actionData = [
            'player_id' => $player->id,
            'turn_number' => $turnNumber,
            'action_type' => $action->getType(),
            'action_details' => $action->toArray(),
            'status' => $validationResult->isValid ? 'success' : 'invalid',
            'error_code' => $validationResult->errorCode,
            'timestamp_client' => now(),
        ];

        // Add coordination fields if provided
        if ($coordinationGroup !== null) {
            $actionData['coordination_group'] = $coordinationGroup;
            $actionData['is_coordinated'] = true;
            if ($coordinationSequence !== null) {
                $actionData['coordination_sequence'] = $coordinationSequence;
            }
        }

        /** @var Action */
        return $game->actions()->create($actionData);
    }

    /**
     * Record a successful action (convenience method).
     */
    public function recordSuccess(
        Game $game,
        Player $player,
        GameActionContract $action,
        int $turnNumber,
        ?string $coordinationGroup = null,
        ?int $coordinationSequence = null,
    ): Action {
        return $this->record(
            $game,
            $player,
            $action,
            ValidationResult::valid(),
            $turnNumber,
            $coordinationGroup,
            $coordinationSequence
        );
    }

    /**
     * Record a failed action (convenience method).
     */
    public function recordFailure(
        Game $game,
        Player $player,
        GameActionContract $action,
        ValidationResult $validationResult,
        int $turnNumber
    ): Action {
        return $this->record($game, $player, $action, $validationResult, $turnNumber);
    }
}
