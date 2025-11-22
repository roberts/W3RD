<?php

declare(strict_types=1);

namespace App\GameEngine\Interfaces;

use App\Models\Auth\User;

interface VisibilityDriver
{
    public function redact(object $gameState, User $player): object;
}
