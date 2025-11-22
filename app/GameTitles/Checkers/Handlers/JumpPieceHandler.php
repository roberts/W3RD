<?php

declare(strict_types=1);

namespace App\GameTitles\Checkers\Handlers;

use App\Enums\GameErrorCode;
use App\GameEngine\Actions\JumpPiece;
use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\ValidationResult;
use App\GameTitles\Checkers\CheckersBoard;
use App\GameTitles\Checkers\Enums\CheckersActionError;

class JumpPieceHandler implements GameActionHandlerInterface
{
    public function validate(object $state, object $action): ValidationResult
    {
        if (! ($state instanceof CheckersBoard)) {
            return ValidationResult::invalid(GameErrorCode::INVALID_STATE->value, 'State must be Checkers CheckersBoard');
        }
        if (! ($action instanceof JumpPiece)) {
            return ValidationResult::invalid(GameErrorCode::INVALID_ACTION_TYPE->value, 'Action must be JumpPiece');
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

        // 5. Check captured piece
        $capturedPiece = $state->getPieceAt($action->capturedRow, $action->capturedCol);
        if ($capturedPiece === null) {
            return ValidationResult::invalid(CheckersActionError::INVALID_JUMP->value, 'No piece to capture');
        }
        if ($capturedPiece['player'] === $state->currentPlayerUlid) {
            return ValidationResult::invalid(CheckersActionError::INVALID_JUMP->value, 'Cannot capture your own piece');
        }

        // 6. Check direction and distance
        $playerState = $state->players[$state->currentPlayerUlid];
        $isKing = $piece['king'];

        $rowDiff = $action->toRow - $action->fromRow;
        $colDiff = abs($action->toCol - $action->fromCol);

        if ($colDiff !== 2) {
            return ValidationResult::invalid(CheckersActionError::INVALID_JUMP->value, 'Jump must be diagonal by 2 squares');
        }
        if (abs($rowDiff) !== 2) {
            return ValidationResult::invalid(CheckersActionError::INVALID_JUMP->value, 'Jump must be diagonal by 2 squares');
        }

        // Check if captured piece is in between
        $midRow = ($action->fromRow + $action->toRow) / 2;
        $midCol = ($action->fromCol + $action->toCol) / 2;

        if ($midRow !== $action->capturedRow || $midCol !== $action->capturedCol) {
            return ValidationResult::invalid(CheckersActionError::INVALID_CAPTURE->value, 'Captured piece must be jumped over');
        }

        if (! $isKing) {
            if ($playerState->color === 'red') {
                // Red moves UP (decreasing row index)
                if ($rowDiff !== -2) {
                    return ValidationResult::invalid(CheckersActionError::INVALID_MOVE_DIRECTION->value, 'Red pieces must jump forward');
                }
            } else {
                // Black moves DOWN (increasing row index)
                if ($rowDiff !== 2) {
                    return ValidationResult::invalid(CheckersActionError::INVALID_MOVE_DIRECTION->value, 'Black pieces must jump forward');
                }
            }
        }

        return ValidationResult::valid();
    }

    public function apply(object $state, object $action): object
    {
        if (! ($state instanceof CheckersBoard) || ! ($action instanceof JumpPiece)) {
            return $state;
        }

        // Remove captured piece
        $state = $state->withRemovedPiece($action->capturedRow, $action->capturedCol);

        // Move jumping piece
        $newState = $state->withMovedPiece(
            $action->fromRow,
            $action->fromCol,
            $action->toRow,
            $action->toCol
        );

        // Switch turn
        return new CheckersBoard(
            players: $newState->players,
            currentPlayerUlid: $this->getNextPlayerUlid($newState),
            winnerUlid: $newState->winnerUlid,
            phase: $newState->phase,
            status: $newState->status,
            board: $newState->board,
            isDraw: $newState->isDraw,
        );
    }

    public function getAvailableOptions(object $state, string $playerUlid): array
    {
        return [];
    }

    private function isWithinBounds(int $row, int $col): bool
    {
        return $row >= 0 && $row < 8 && $col >= 0 && $col < 8;
    }

    private function getNextPlayerUlid(CheckersBoard $gameState): string
    {
        $playerUlids = array_keys($gameState->players);
        $currentIndex = array_search($gameState->currentPlayerUlid, $playerUlids);
        $nextIndex = ($currentIndex + 1) % count($playerUlids);

        return $playerUlids[$nextIndex];
    }
}
