<?php

declare(strict_types=1);

namespace App\Games\Checkers\Handlers;

use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\ValidationResult;
use App\GameEngine\Actions\DoubleJumpPiece;
use App\Games\Checkers\CheckersBoard;
use App\Games\Checkers\Enums\CheckersActionError;
use App\Enums\GameErrorCode;

class DoubleJumpPieceHandler implements GameActionHandlerInterface
{
    public function validate(object $state, object $action): ValidationResult
    {
        if (! ($state instanceof CheckersBoard)) {
             return ValidationResult::invalid(GameErrorCode::INVALID_STATE->value, 'State must be Checkers CheckersBoard');
        }
        if (! ($action instanceof DoubleJumpPiece)) {
             return ValidationResult::invalid(GameErrorCode::INVALID_ACTION_TYPE->value, 'Action must be DoubleJumpPiece');
        }

        // Simplified validation for now - assuming client sends valid moves or we trust the sequence
        // In a real implementation, we would validate each jump step.
        
        return ValidationResult::valid();
    }

    public function apply(object $state, object $action): object
    {
        if (! ($state instanceof CheckersBoard) || ! ($action instanceof DoubleJumpPiece)) {
            return $state;
        }

        // Remove both captured pieces
        $newState = $state
            ->withRemovedPiece($action->capturedRow1, $action->capturedCol1)
            ->withRemovedPiece($action->capturedRow2, $action->capturedCol2);

        // Move through mid point to final position
        // Note: withMovedPiece handles promotion if landing on end row.
        $newState = $newState->withMovedPiece(
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

    private function getNextPlayerUlid(CheckersBoard $gameState): string
    {
        $playerUlids = array_keys($gameState->players);
        $currentIndex = array_search($gameState->currentPlayerUlid, $playerUlids);
        $nextIndex = ($currentIndex + 1) % count($playerUlids);
        return $playerUlids[$nextIndex];
    }
}
