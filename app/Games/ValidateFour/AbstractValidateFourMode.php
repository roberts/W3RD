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
     * @return string|null The winning player's ULID, or null if game continues
     */
    public function checkEndCondition(object $gameState): ?string
    {
        if (!($gameState instanceof ValidateFourGameState)) {
            return null;
        }

        // Check for a winner
        return $this->checkForWinner($gameState);
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
        if ($action->column < 0 || $action->column >= $state->columns) {
            return false;
        }

        // Check if column has space
        return $state->getLowestEmptyRow($action->column) !== null;
    }

    /**
     * Apply a drop disc action to the game state.
     * Returns a new immutable game state.
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

        // Place the disc and switch player
        return $state
            ->withDiscAt($row, $action->column, $state->currentPlayerUlid)
            ->withNextPlayer();
    }

    /**
     * Check if there is a winner on the board.
     *
     * @param ValidateFourGameState $state
     * @return string|null The winner's ULID, or null if no winner
     */
    protected function checkForWinner(ValidateFourGameState $state): ?string
    {
        // Check all possible starting positions for winning lines
        for ($row = 0; $row < $state->rows; $row++) {
            for ($col = 0; $col < $state->columns; $col++) {
                $disc = $state->getDiscAt($row, $col);
                if ($disc === null) {
                    continue;
                }

                // Check horizontal (right)
                if ($this->checkLine($state, $row, $col, 0, 1, $disc)) {
                    return $disc;
                }

                // Check vertical (down)
                if ($this->checkLine($state, $row, $col, 1, 0, $disc)) {
                    return $disc;
                }

                // Check diagonal (down-right)
                if ($this->checkLine($state, $row, $col, 1, 1, $disc)) {
                    return $disc;
                }

                // Check diagonal (down-left)
                if ($this->checkLine($state, $row, $col, 1, -1, $disc)) {
                    return $disc;
                }
            }
        }

        return null;
    }

    /**
     * Check if there is a winning line starting from a position in a direction.
     *
     * @param ValidateFourGameState $state
     * @param int $startRow
     * @param int $startCol
     * @param int $deltaRow Row direction (-1, 0, or 1)
     * @param int $deltaCol Column direction (-1, 0, or 1)
     * @param string $playerUlid The player ULID to check for
     * @return bool
     */
    protected function checkLine(
        ValidateFourGameState $state,
        int $startRow,
        int $startCol,
        int $deltaRow,
        int $deltaCol,
        string $playerUlid
    ): bool {
        $count = 0;
        $row = $startRow;
        $col = $startCol;

        while ($row >= 0 && $row < $state->rows && $col >= 0 && $col < $state->columns) {
            if ($state->getDiscAt($row, $col) === $playerUlid) {
                $count++;
                if ($count >= $state->connectCount) {
                    return true;
                }
            } else {
                break;
            }

            $row += $deltaRow;
            $col += $deltaCol;
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
