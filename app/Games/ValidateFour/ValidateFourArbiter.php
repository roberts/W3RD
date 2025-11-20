<?php

declare(strict_types=1);

namespace App\Games\ValidateFour;

use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameArbiterContract;

class ValidateFourArbiter implements GameArbiterContract
{
    public function checkWinCondition(object $gameState): GameOutcome
    {
        if (! ($gameState instanceof ValidateFourBoard)) {
            return GameOutcome::inProgress();
        }

        // Check for winner
        $winner = $this->findWinner($gameState);
        if ($winner) {
            return GameOutcome::win(
                $winner,
                null,
                'four_in_a_row'
            );
        }

        // Check for draw (board full)
        if ($gameState->isBoardFull()) {
            return GameOutcome::draw('board_full');
        }

        return GameOutcome::inProgress();
    }

    protected function findWinner(ValidateFourBoard $gameState): ?string
    {
        // Horizontal
        for ($r = 0; $r < $gameState->rows; $r++) {
            for ($c = 0; $c <= $gameState->columns - 4; $c++) {
                $player = $gameState->getPieceAt($r, $c);
                if ($player &&
                    $player === $gameState->getPieceAt($r, $c + 1) &&
                    $player === $gameState->getPieceAt($r, $c + 2) &&
                    $player === $gameState->getPieceAt($r, $c + 3)) {
                    return $player;
                }
            }
        }

        // Vertical
        for ($r = 0; $r <= $gameState->rows - 4; $r++) {
            for ($c = 0; $c < $gameState->columns; $c++) {
                $player = $gameState->getPieceAt($r, $c);
                if ($player &&
                    $player === $gameState->getPieceAt($r + 1, $c) &&
                    $player === $gameState->getPieceAt($r + 2, $c) &&
                    $player === $gameState->getPieceAt($r + 3, $c)) {
                    return $player;
                }
            }
        }

        // Diagonal (Positive Slope)
        for ($r = 0; $r <= $gameState->rows - 4; $r++) {
            for ($c = 0; $c <= $gameState->columns - 4; $c++) {
                $player = $gameState->getPieceAt($r, $c);
                if ($player &&
                    $player === $gameState->getPieceAt($r + 1, $c + 1) &&
                    $player === $gameState->getPieceAt($r + 2, $c + 2) &&
                    $player === $gameState->getPieceAt($r + 3, $c + 3)) {
                    return $player;
                }
            }
        }

        // Diagonal (Negative Slope)
        for ($r = 3; $r < $gameState->rows; $r++) {
            for ($c = 0; $c <= $gameState->columns - 4; $c++) {
                $player = $gameState->getPieceAt($r, $c);
                if ($player &&
                    $player === $gameState->getPieceAt($r - 1, $c + 1) &&
                    $player === $gameState->getPieceAt($r - 2, $c + 2) &&
                    $player === $gameState->getPieceAt($r - 3, $c + 3)) {
                    return $player;
                }
            }
        }

        return null;
    }
}
