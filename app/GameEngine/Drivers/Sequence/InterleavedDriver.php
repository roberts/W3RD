<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\Sequence;

use App\GameEngine\Interfaces\SequenceDriver;
use App\Models\Auth\User;
use App\Models\Game\Game;

class InterleavedDriver implements SequenceDriver
{
    public function isPlayerTurn(Game $game, User $player): bool
    {
        // Allows for primary turn-taker and out-of-turn reactions.
        // Complex logic to determine if player is the current actor OR has a valid reaction.
        return $game->current_player_id === $player->id; // Simplified for now
    }

    public function advanceTurn(Game $game): Game
    {
        // Standard turn advancement, but might be interrupted by reactions.
        $players = $game->players;
        $currentPlayerIndex = $players->search(fn ($p) => $p->id === $game->current_player_id);

        $nextPlayerIndex = ($currentPlayerIndex + 1) % $players->count();
        $game->current_player_id = $players[$nextPlayerIndex]->id;

        return $game;
    }
}
