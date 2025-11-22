<?php

declare(strict_types=1);

namespace App\GameTitles\ConnectFour\Handlers;

use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\ValidationResult;
use App\GameTitles\ConnectFour\Actions\PopOut;
use App\GameTitles\ConnectFour\ConnectFourBoard;
use App\GameTitles\ConnectFour\Enums\PopOutModeError;

class PopOutHandler implements GameActionHandlerInterface
{
    public function validate(object $gameState, object $action): ValidationResult
    {
        if (! ($gameState instanceof ConnectFourBoard)) {
            return ValidationResult::invalid('INVALID_STATE_TYPE', 'Game state must be a ConnectFourBoard instance');
        }

        if (! ($action instanceof PopOut)) {
            return ValidationResult::invalid('INVALID_ACTION_TYPE', 'Action must be a PopOut instance');
        }

        // Validate column range
        if ($action->column < 0 || $action->column >= $gameState->columns) {
            return ValidationResult::invalid(
                'INVALID_COLUMN',
                'Column must be between 0 and '.($gameState->columns - 1),
                ['column' => $action->column]
            );
        }

        // Validate bottom piece
        $bottomPiece = $gameState->getPieceAt($gameState->rows - 1, $action->column);

        if ($bottomPiece === null) {
            return ValidationResult::invalid(
                PopOutModeError::NO_PIECE_AT_BOTTOM->value,
                'No piece at the bottom of this column',
                ['column' => $action->column]
            );
        }

        if ($bottomPiece !== $gameState->currentPlayerUlid) {
            return ValidationResult::invalid(
                PopOutModeError::NOT_YOUR_PIECE->value,
                'You can only pop out your own piece from the bottom',
                ['column' => $action->column]
            );
        }

        return ValidationResult::valid();
    }

    public function apply(object $gameState, object $action): object
    {
        if (! ($gameState instanceof ConnectFourBoard) || ! ($action instanceof PopOut)) {
            return $gameState;
        }

        // Logic to shift column down
        $newBoard = $gameState->board;
        $col = $action->column;

        // Shift pieces down
        for ($row = $gameState->rows - 1; $row > 0; $row--) {
            $newBoard[$row][$col] = $newBoard[$row - 1][$col];
        }
        $newBoard[0][$col] = null;

        return $gameState
            ->withBoard($newBoard)
            ->withNextPlayer();
    }

    public function getAvailableOptions(object $state, string $playerUlid): array
    {
        if (! ($state instanceof ConnectFourBoard)) {
            return [];
        }

        if ($state->currentPlayerUlid !== $playerUlid) {
            return [];
        }

        $columns = [];
        $bottomRow = $state->rows - 1;

        for ($col = 0; $col < $state->columns; $col++) {
            if ($state->getPieceAt($bottomRow, $col) === $playerUlid) {
                $columns[] = $col;
            }
        }

        return [
            'columns' => $columns,
        ];
    }
}
