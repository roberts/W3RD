<?php

namespace App\Games\ValidateFour;

use App\Games\BaseBoardGameTitle;
use App\Games\GameOutcome;
use App\Games\ValidationResult;
use App\Games\ValidateFour\GameState;
use App\Games\ValidateFour\Actions\DropDisc;
use App\Models\Game\Game;
use App\Models\Game\Player;
use Carbon\Carbon;

abstract class BaseValidateFour extends BaseBoardGameTitle
{
    /**
     * Default turn time limit in seconds.
     * Subclasses can override this constant for mode-specific timing.
     */
    protected const DEFAULT_TURN_TIME_SECONDS = 30;

    /**
     * Grace period in seconds to account for network latency.
     * Added to the turn time limit when calculating deadlines.
     */
    protected const NETWORK_GRACE_PERIOD_SECONDS = 2;

    /**
     * Default penalty when a turn times out.
     * Valid values: 'none', 'pass', 'forfeit'
     */
    protected const DEFAULT_TIMEOUT_PENALTY = 'forfeit';

    protected function getGameStateClass(): string
    {
        return GameState::class;
    }

    /**
     * Create initial game state for a new game.
     *
     * Validate Four requires exactly 2 players.
     *
     * @param string ...$playerUlids Player ULIDs (must be exactly 2)
     * @return GameState
     * @throws \InvalidArgumentException If not exactly 2 players provided
     */
    public function createInitialState(string ...$playerUlids): object
    {
        if (count($playerUlids) !== 2) {
            throw new \InvalidArgumentException('Validate Four requires exactly 2 players');
        }

        return GameState::createNew(
            playerOneUlid: $playerUlids[0],
            playerTwoUlid: $playerUlids[1],
            columns: 7,
            rows: 6,
            connectCount: 4
        );
    }

    /**
     * Get the state class name.
     *
     * @return string
     */
    public function getStateClass(): string
    {
        return GameState::class;
    }

    /**
     * Get the action factory class name.
     *
     * @return string
     */
    public function getActionFactory(): string
    {
        return ActionFactory::class;
    }

    /**
     * Returns the base rules common to all Validate Four game titles.
     *
     * Child classes should override this method to add or modify rules
     * and merge them with this base configuration.
     *
     * @return array The structured rules array.
     */
    public static function getRules(): array
    {
        return [
            'title' => 'Validate Four',
            'description' => 'Be the first player to connect four of your discs in a row—horizontally, vertically, or diagonally.',
            'sections' => [
                [
                    'title' => 'Core Gameplay',
                    'content' => <<<MARKDOWN
                    *   Players take turns dropping one of their colored discs from the top into a column.
                    *   The disc falls to the lowest available space within the column.
                    *   The first player to form a line of four of their discs wins.
                    MARKDOWN,
                ],
            ],
        ];
    }

    /**
     * Validate a player's action.
     *
     * @param object $action The action DTO (DropDisc, PopOut, etc)
     * @return ValidationResult
     */
    public function validateAction(object $action): ValidationResult
    {
        if (!($this->gameState instanceof GameState)) {
            return ValidationResult::invalid(
                'INVALID_STATE_TYPE',
                'Game state must be a GameState instance'
            );
        }

        if ($action instanceof DropDisc) {
            return $this->validateDropDisc($this->gameState, $action);
        }

        return ValidationResult::invalid(
            'UNKNOWN_ACTION_TYPE',
            'Action type not recognized for Validate Four'
        );
    }

    /**
     * Apply a valid action to the game state.
     *
     * @param object $gameState GameState instance
     * @param object $action Action DTO
     * @return object Updated GameState
     */
    public function applyAction(object $gameState, object $action): object
    {
        if ($action instanceof DropDisc) {
            return $this->applyDropDisc($gameState, $action);
        }

        return $this->gameState;
    }

    /**
     * Check if the game has ended (win or draw).
     *
     * @return GameOutcome The game outcome
     */
    public function checkEndCondition(): GameOutcome
    {
        if (!($this->gameState instanceof GameState)) {
            return GameOutcome::inProgress();
        }

        // Check for a winner
        $winnerUlid = $this->checkForWinner($this->gameState);
        if ($winnerUlid) {
            return GameOutcome::win($winnerUlid, 'four_in_a_row');
        }

        // Check for draw (board full)
        if ($this->gameState->isBoardFull()) {
            return GameOutcome::draw('board_full');
        }

        return GameOutcome::inProgress();
    }

    /**
     * Get available actions for a specific player.
     *
     * @param string $playerUlid The player's ULID
     * @return array<string, mixed> Map of action types to their available parameters
     */
    public function getAvailableActions(string $playerUlid): array
    {
        if (!($this->gameState instanceof GameState)) {
            return [];
        }

        // If not player's turn, no actions available
        if ($this->gameState->currentPlayerUlid !== $playerUlid) {
            return [];
        }

        // Find columns that aren't full
        $availableColumns = [];
        for ($col = 0; $col < $this->gameState->columns; $col++) {
            if ($this->gameState->getLowestEmptyRow($col) !== null) {
                $availableColumns[] = $col;
            }
        }

        return [
            'drop_disc' => [
                'columns' => $availableColumns,
            ],
        ];
    }

    /**
     * Validate a drop disc action.
     *
     * @param GameState $state
     * @param DropDisc $action
     * @return ValidationResult
     */
    protected function validateDropDisc(GameState $state, DropDisc $action): ValidationResult
    {
        // Check if column index is valid
        if ($action->column < 0 || $action->column >= $state->columns) {
            return ValidationResult::invalid(
                'INVALID_COLUMN',
                sprintf('Column must be between 0 and %d', $state->columns - 1),
                ['column' => $action->column, 'max' => $state->columns - 1]
            );
        }

        // Check if column has space
        if ($state->getLowestEmptyRow($action->column) === null) {
            return ValidationResult::invalid(
                'COLUMN_FULL',
                sprintf('Column %d is full', $action->column),
                ['column' => $action->column]
            );
        }

        return ValidationResult::valid();
    }

    /**
     * Apply a drop disc action to the game state.
     * Returns a new immutable game state.
     *
     * @param GameState $state
     * @param DropDisc $action
     * @return GameState
     */
    protected function applyDropDisc(GameState $state, DropDisc $action): GameState
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
     * @param GameState $state
     * @return string|null The winner's ULID, or null if no winner
     */
    protected function checkForWinner(GameState $state): ?string
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
     * @param GameState $state
     * @param int $startRow
     * @param int $startCol
     * @param int $deltaRow Row direction (-1, 0, or 1)
     * @param int $deltaCol Column direction (-1, 0, or 1)
     * @param string $playerUlid The player ULID to check for
     * @return bool
     */
    protected function checkLine(
        GameState $state,
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
     * Returns the default constant value, but can be overridden by specific modes.
     *
     * @return int Time limit in seconds
     */
    public function getTimelimit(): int
    {
        return static::DEFAULT_TURN_TIME_SECONDS;
    }

    /**
     * Get the deadline timestamp for the current action.
     * Calculated as: last action time + timelimit + grace period for network latency
     *
     * @param object $gameState GameState instance
     * @param Game $game The game model instance
     * @return Carbon The deadline timestamp
     */
    public function getActionDeadline(object $gameState, Game $game): Carbon
    {
        // Get the last action's timestamp, or game start time if no actions yet
        $lastAction = $game->actions()->latest()->first();
        $baseTime = $lastAction ? $lastAction->created_at : $game->started_at;

        // Add timelimit + grace period for network latency
        return $baseTime->copy()->addSeconds(
            $this->getTimelimit() + static::NETWORK_GRACE_PERIOD_SECONDS
        );
    }

    /**
     * Get the penalty applied when an action times out.
     * Returns the default constant value, but can be overridden by specific modes.
     *
     * @return string 'none', 'pass', or 'forfeit'
     */
    public function getTimeoutPenalty(): string
    {
        return static::DEFAULT_TIMEOUT_PENALTY;
    }
}
