<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\Sequence;

use App\GameEngine\Interfaces\SequenceDriver;
use App\Models\Auth\User;
use App\Models\Game\Game;

class SequentialDriver implements SequenceDriver
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
