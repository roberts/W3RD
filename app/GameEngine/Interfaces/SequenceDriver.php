<?php

declare(strict_types=1);

namespace App\GameEngine\Interfaces;

use App\Models\Game\Game;
use App\Models\Auth\User;

interface SequenceDriver
{
    public function isPlayerTurn(Game $game, User $player): bool;

    public function advanceTurn(Game $game): Game;
}
