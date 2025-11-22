<?php

declare(strict_types=1);

namespace App\GameTitles\Checkers;

use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameArbiterContract;

class CheckersArbiter implements GameArbiterContract
{
    public function checkWinCondition(object $gameState): GameOutcome
    {
        if (! ($gameState instanceof CheckersBoard)) {
            return GameOutcome::inProgress();
        }

        // Check for winner (no pieces remaining)
        foreach ($gameState->players as $player) {
            if ($player->piecesRemaining === 0) {
                // Other player wins
                $otherPlayers = array_filter(
                    $gameState->players,
                    fn ($p) => $p->ulid !== $player->ulid
                );
                $winner = reset($otherPlayers);

                if ($winner !== false) {
                    return GameOutcome::win($winner->ulid, null, 'no_pieces_remaining');
                }
            }
        }

        // Check for draw (no legal moves - stalemate)
        if ($gameState->isDraw) {
            return GameOutcome::draw('stalemate');
        }

        return GameOutcome::inProgress();
    }
}
