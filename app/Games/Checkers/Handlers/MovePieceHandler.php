<?php

declare(strict_types=1);

namespace App\Games\Checkers\Handlers;

use App\Enums\GameErrorCode;
use App\GameEngine\Actions\MovePiece;
use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\ValidationResult;
use App\Games\Checkers\CheckersBoard;
use App\Games\Checkers\Enums\CheckersActionError;

class MovePieceHandler implements GameActionHandlerInterface
{
    public function validate(object $state, object $action): ValidationResult
    {
        if (! ($state instanceof CheckersBoard)) {
            return ValidationResult::invalid(GameErrorCode::INVALID_STATE->value, 'State must be Checkers CheckersBoard');
        }
        if (! ($action instanceof MovePiece)) {
            return ValidationResult::invalid(GameErrorCode::INVALID_ACTION_TYPE->value, 'Action must be MovePiece');
        }

        // 1. Check bounds
        if (! $this->isWithinBounds($action->toRow, $action->toCol)) {
            return ValidationResult::invalid(CheckersActionError::POSITION_OUT_OF_BOUNDS->value, 'Destination is out of bounds');
        }

        // 2. Check if destination is empty
        if ($state->getPieceAt($action->toRow, $action->toCol) !== null) {
            return ValidationResult::invalid(CheckersActionError::DESTINATION_OCCUPIED->value, 'Destination is occupied');
        }

        // 3. Check if piece exists at source
        $piece = $state->getPieceAt($action->fromRow, $action->fromCol);
        if ($piece === null) {
            return ValidationResult::invalid(CheckersActionError::INVALID_PIECE_SELECTION->value, 'No piece at source');
        }

        // 4. Check if piece belongs to current player
        if ($piece['player'] !== $state->currentPlayerUlid) {
            return ValidationResult::invalid(CheckersActionError::INVALID_PIECE_SELECTION->value, 'Piece does not belong to you');
        }

        // 5. Check direction and distance
        $playerState = $state->players[$state->currentPlayerUlid];
        $isKing = $piece['king'];

        $rowDiff = $action->toRow - $action->fromRow;
        $colDiff = abs($action->toCol - $action->fromCol);

        if ($colDiff !== 1) {
            return ValidationResult::invalid(CheckersActionError::MUST_MOVE_DIAGONALLY->value, 'Move must be diagonal by 1 square');
        }

        if ($isKing) {
            if (abs($rowDiff) !== 1) {
                return ValidationResult::invalid(CheckersActionError::INVALID_MOVE_DISTANCE->value, 'King must move 1 square diagonally');
            }
        } else {
            if ($playerState->color === 'red') {
                // Red moves UP (decreasing row index)
                if ($rowDiff !== -1) {
                    return ValidationResult::invalid(CheckersActionError::INVALID_MOVE_DIRECTION->value, 'Red pieces must move forward');
                }
            } else {
                // Black moves DOWN (increasing row index)
                if ($rowDiff !== 1) {
                    return ValidationResult::invalid(CheckersActionError::INVALID_MOVE_DIRECTION->value, 'Black pieces must move forward');
                }
            }
        }

        return ValidationResult::valid();
    }

    public function apply(object $state, object $action): object
    {
        if (! ($state instanceof CheckersBoard) || ! ($action instanceof MovePiece)) {
            return $state;
        }

        $newState = $state->withMovedPiece(
            $action->fromRow,
            $action->fromCol,
            $action->toRow,
            $action->toCol
        );

        // Switch turn
        return $newState->withNextPlayer();
    }

    public function getAvailableOptions(object $state, string $playerUlid): array
    {
        // TODO: Implement move generation for AI/UI
        return [];
    }

    private function isWithinBounds(int $row, int $col): bool
    {
        return $row >= 0 && $row < 8 && $col >= 0 && $col < 8;
    }
}
