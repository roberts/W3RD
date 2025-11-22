<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\Sequence;

use App\Models\Auth\User;
use App\Models\Game\Game;

/**
 * Sequential turn-based gameplay.
 * Players take turns one after another in a fixed order.
 *
 * Examples: Chess, Checkers, Connect Four
 */
trait SequentialTurns
{
    public function isPlayerTurn(Game $game, User $player): bool
    {
        return $game->current_player_id === $player->id;
    }

    public function advanceTurn(Game $game): Game
    {
        $players = $game->players;
        $currentPlayerIndex = $players->search(fn ($p) => $p->id === $game->current_player_id);

        $nextPlayerIndex = ($currentPlayerIndex + 1) % $players->count();
        $game->current_player_id = $players[$nextPlayerIndex]->id;

        return $game;
    }
}
