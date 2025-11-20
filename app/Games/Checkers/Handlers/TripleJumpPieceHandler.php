<?php

declare(strict_types=1);

namespace App\Games\Checkers\Handlers;

use App\Enums\GameErrorCode;
use App\GameEngine\Actions\TripleJumpPiece;
use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\ValidationResult;
use App\Games\Checkers\CheckersBoard;

class TripleJumpPieceHandler implements GameActionHandlerInterface
{
    public function validate(object $state, object $action): ValidationResult
    {
        if (! ($state instanceof CheckersBoard)) {
            return ValidationResult::invalid(GameErrorCode::INVALID_STATE->value, 'State must be Checkers CheckersBoard');
        }
        if (! ($action instanceof TripleJumpPiece)) {
            return ValidationResult::invalid(GameErrorCode::INVALID_ACTION_TYPE->value, 'Action must be TripleJumpPiece');
        }

        return ValidationResult::valid();
    }

    public function apply(object $state, object $action): object
    {
        if (! ($state instanceof CheckersBoard) || ! ($action instanceof TripleJumpPiece)) {
            return $state;
        }

        // Remove all three captured pieces
        $newState = $state
            ->withRemovedPiece($action->capturedRow1, $action->capturedCol1)
            ->withRemovedPiece($action->capturedRow2, $action->capturedCol2)
            ->withRemovedPiece($action->capturedRow3, $action->capturedCol3);

        // Move to final position
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
