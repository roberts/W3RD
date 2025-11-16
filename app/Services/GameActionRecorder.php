<?php

declare(strict_types=1);

namespace App\Services;

use App\Games\ValidationResult;
use App\Interfaces\GameActionContract;
use App\Models\Game\Game;
use App\Models\Game\Player;

/**
 * Service for recording game actions to the database.
 *
 * Centralizes action logging logic that was previously embedded in the controller.
 * Records both successful and failed actions for debugging and replay capabilities.
 */
class GameActionRecorder
{
    /**
     * Record a game action to the database.
     *
     * @param Game $game The game instance
     * @param Player $player The player who performed the action
     * @param GameActionContract $action The action that was performed
     * @param ValidationResult $validationResult The validation result
     * @param int $turnNumber The current turn number
     * @return void
     */
    public function record(
        Game $game,
        Player $player,
        GameActionContract $action,
        ValidationResult $validationResult,
        int $turnNumber
    ): void {
        $game->actions()->create([
            'player_id' => $player->id,
            'turn_number' => $turnNumber,
            'action_type' => $action->getType(),
            'action_details' => $action->toArray(),
            'status' => $validationResult->isValid ? 'success' : 'invalid',
            'error_code' => $validationResult->errorCode,
            'timestamp_client' => now(),
        ]);
    }

    /**
     * Record a successful action (convenience method).
     *
     * @param Game $game
     * @param Player $player
     * @param GameActionContract $action
     * @param int $turnNumber
     * @return void
     */
    public function recordSuccess(
        Game $game,
        Player $player,
        GameActionContract $action,
        int $turnNumber
    ): void {
        $this->record($game, $player, $action, ValidationResult::valid(), $turnNumber);
    }

    /**
     * Record a failed action (convenience method).
     *
     * @param Game $game
     * @param Player $player
     * @param GameActionContract $action
     * @param ValidationResult $validationResult
     * @param int $turnNumber
     * @return void
     */
    public function recordFailure(
        Game $game,
        Player $player,
        GameActionContract $action,
        ValidationResult $validationResult,
        int $turnNumber
    ): void {
        $this->record($game, $player, $action, $validationResult, $turnNumber);
    }
}
