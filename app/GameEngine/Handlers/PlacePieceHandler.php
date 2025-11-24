<?php

declare(strict_types=1);

namespace App\GameEngine\Handlers;

use App\GameEngine\Actions\PlacePiece;
use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\ValidationResult;

class PlacePieceHandler implements GameActionHandlerInterface
{
    protected bool $gravity;

    /**
     * @param  array<string, mixed>  $rules
     */
    public function __construct(array $rules = [])
    {
        $this->gravity = $rules['gravity'] ?? false;
    }

    public function validate(object $state, object $action): ValidationResult
    {
        if (! ($action instanceof PlacePiece)) {
            return ValidationResult::invalid('INVALID_ACTION_TYPE', 'Action must be PlacePiece');
        }

        // Validate Column Bounds
        if ($action->column < 0 || $action->column >= $state->columns) {
            return ValidationResult::invalid(
                'INVALID_COLUMN',
                sprintf('Column must be between 0 and %d', $state->columns - 1),
                ['column' => $action->column, 'max' => $state->columns - 1]
            );
        }

        // Gravity Logic: Check if column is full
        if ($this->gravity) {
            if ($this->getLowestEmptyRow($state, $action->column) === null) {
                return ValidationResult::invalid(
                    'COLUMN_FULL',
                    sprintf('Column %d is full', $action->column),
                    ['column' => $action->column]
                );
            }
        } else {
            // Non-gravity logic (e.g. TicTacToe or Go) would check if specific cell is empty
            // For now, we focus on Connect 4 style
            if ($action->row === null) {
                return ValidationResult::invalid('MISSING_ROW', 'Row is required for non-gravity placement');
            }
            // Check row bounds and if cell is empty...
        }

        return ValidationResult::valid();
    }

    public function apply(object $state, object $action): object
    {
        if (! ($action instanceof PlacePiece)) {
            return $state;
        }

        $row = $action->row;

        if ($this->gravity) {
            $row = $this->getLowestEmptyRow($state, $action->column);
            if ($row === null) {
                return $state; // Should be caught by validate
            }
        }

        // We assume state has a method to place a piece or we modify the board directly if it's a DTO
        // For ValidateFour GameState, it has withPieceAt
        if (method_exists($state, 'withPieceAt')) {
            return $state
                ->withPieceAt($row, $action->column, $state->currentPlayerUlid)
                ->withNextPlayer();
        }

        return $state;
    }

    public function getAvailableOptions(object $state, string $playerUlid): array
    {
        // If not player's turn, no options?
        // The Kernel or State Machine might handle turn order, but Handler can also check.
        if (isset($state->currentPlayerUlid) && $state->currentPlayerUlid !== $playerUlid) {
            return [];
        }

        $availableColumns = [];
        for ($col = 0; $col < $state->columns; $col++) {
            if ($this->gravity) {
                if ($this->getLowestEmptyRow($state, $col) !== null) {
                    $availableColumns[] = $col;
                }
            } else {
                // For non-gravity, we'd return all empty cells (x,y)
            }
        }

        return [
            'columns' => $availableColumns,
        ];
    }

    protected function getLowestEmptyRow(object $state, int $column): ?int
    {
        // Assuming state has getLowestEmptyRow or we implement it here based on state->board
        if (method_exists($state, 'getLowestEmptyRow')) {
            return $state->getLowestEmptyRow($column);
        }

        // Fallback if state is just a data object with board
        if (isset($state->board) && isset($state->rows)) {
            for ($row = $state->rows - 1; $row >= 0; $row--) {
                if ($state->board[$row][$column] === null) {
                    return $row;
                }
            }
        }

        return null;
    }
}
