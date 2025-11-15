<?php

namespace App\Games\ValidateFour;

use App\Games\ValidateFour\Actions\DropDisc;
use App\Interfaces\GameTitleContract;
use App\Models\Game\Game;
use App\Models\Game\Player;
use Carbon\Carbon;

abstract class AbstractValidateFourMode implements GameTitleContract
{
    /**
     * Validate a player's action.
     *
     * @param object $gameState ValidateFourGameState instance
     * @param object $action Action DTO (DropDisc, PopOut, etc.)
     * @return bool
     */
    public function validateAction(object $gameState, object $action): bool
    {
        if (!($gameState instanceof ValidateFourGameState)) {
            return false;
        }

        if ($action instanceof DropDisc) {
            return $this->validateDropDisc($gameState, $action);
        }

        return false;
    }

    /**
     * Apply a valid action to the game state.
     *
     * @param object $gameState ValidateFourGameState instance
     * @param object $action Action DTO
     * @return object Updated ValidateFourGameState
     */
    public function applyAction(object $gameState, object $action): object
    {
        if ($action instanceof DropDisc) {
            return $this->applyDropDisc($gameState, $action);
        }

        return $gameState;
    }

    /**
     * Check if the game has ended (win or draw).
     *
     * @param object $gameState ValidateFourGameState instance
     * @return Player|null The winning player, or null if game continues
     */
    public function checkEndCondition(object $gameState): ?Player
    {
        if (!($gameState instanceof ValidateFourGameState)) {
            return null;
        }

        // Check for a winner
        $winnerUlid = $this->checkForWinner($gameState);
        if ($winnerUlid) {
            $gameState->winner_ulid = $winnerUlid;
            return Player::where('ulid', $winnerUlid)->first();
        }

        // Check for a draw
        if ($gameState->isBoardFull()) {
            $gameState->is_draw = true;
        }

        return null;
    }

    /**
     * Validate a drop disc action.
     *
     * @param ValidateFourGameState $state
     * @param DropDisc $action
     * @return bool
     */
    protected function validateDropDisc(ValidateFourGameState $state, DropDisc $action): bool
    {
        // Check if column index is valid
        if ($action->column < 0 || $action->column >= $state->board_width) {
            return false;
        }

        // Check if column has space
        return $state->getLowestEmptyRow($action->column) !== null;
    }

    /**
     * Apply a drop disc action to the game state.
     *
     * @param ValidateFourGameState $state
     * @param DropDisc $action
     * @return ValidateFourGameState
     */
    protected function applyDropDisc(ValidateFourGameState $state, DropDisc $action): ValidateFourGameState
    {
        $row = $state->getLowestEmptyRow($action->column);
        if ($row === null) {
            return $state; // Should not happen if validation passed
        }

        $playerNumber = $state->getPlayerNumber($state->current_player_ulid);
        if ($playerNumber === null) {
            return $state; // Invalid player
        }

        $state->setDiscAt($action->column, $row, $playerNumber);

        // Switch to next player
        $currentIndex = array_search($state->current_player_ulid, $state->player_ulids);
        $nextIndex = ($currentIndex + 1) % count($state->player_ulids);
        $state->current_player_ulid = $state->player_ulids[$nextIndex];

        return $state;
    }

    /**
     * Check if there is a winner on the board.
     *
     * @param ValidateFourGameState $state
     * @return string|null The winner's ULID, or null if no winner
     */
    protected function checkForWinner(ValidateFourGameState $state): ?string
    {
        // Check all possible winning lines
        for ($col = 0; $col < $state->board_width; $col++) {
            for ($row = 0; $row < $state->board_height; $row++) {
                $disc = $state->getDiscAt($col, $row);
                if ($disc === 0) {
                    continue;
                }

                // Check horizontal
                if ($this->checkLine($state, $col, $row, 1, 0)) {
                    return $state->player_map[$disc];
                }

                // Check vertical
                if ($this->checkLine($state, $col, $row, 0, 1)) {
                    return $state->player_map[$disc];
                }

                // Check diagonal (down-right)
                if ($this->checkLine($state, $col, $row, 1, 1)) {
                    return $state->player_map[$disc];
                }

                // Check diagonal (up-right)
                if ($this->checkLine($state, $col, $row, 1, -1)) {
                    return $state->player_map[$disc];
                }
            }
        }

        return null;
    }

    /**
     * Check if there is a winning line starting from a position in a direction.
     *
     * @param ValidateFourGameState $state
     * @param int $startCol
     * @param int $startRow
     * @param int $deltaCol
     * @param int $deltaRow
     * @return bool
     */
    protected function checkLine(ValidateFourGameState $state, int $startCol, int $startRow, int $deltaCol, int $deltaRow): bool
    {
        $disc = $state->getDiscAt($startCol, $startRow);
        if ($disc === 0) {
            return false;
        }

        $count = 0;
        $col = $startCol;
        $row = $startRow;

        while ($col >= 0 && $col < $state->board_width && $row >= 0 && $row < $state->board_height) {
            if ($state->getDiscAt($col, $row) === $disc) {
                $count++;
                if ($count >= $state->connect_length) {
                    return true;
                }
            } else {
                break;
            }

            $col += $deltaCol;
            $row += $deltaRow;
        }

        return false;
    }

    /**
     * Get the timelimit in seconds for each action.
     * Default is 30 seconds, but can be overridden by specific modes.
     *
     * @return int
     */
    public function getTimelimit(): int
    {
        return 30;
    }

    /**
     * Get the deadline timestamp for the current action.
     * Calculated as: last action time + timelimit + 2 second grace period
     *
     * @param object $gameState ValidateFourGameState instance
     * @param Game $game The game model instance
     * @return Carbon
     */
    public function getActionDeadline(object $gameState, Game $game): Carbon
    {
        // Get the last action's timestamp, or game start time if no actions yet
        $lastAction = $game->actions()->latest()->first();
        $baseTime = $lastAction ? $lastAction->created_at : $game->started_at;

        // Add timelimit + 2 second grace period for network latency
        return $baseTime->copy()->addSeconds($this->getTimelimit() + 2);
    }

    /**
     * Get the penalty applied when an action times out.
     * Default is 'forfeit', but can be overridden by specific modes.
     *
     * @return string 'none', 'pass', or 'forfeit'
     */
    public function getTimeoutPenalty(): string
    {
        return 'forfeit';
    }
}
