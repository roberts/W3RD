<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\Visibility;

use App\GameEngine\Interfaces\VisibilityDriver;
use App\Models\Auth\User;

class PerfectInformationDriver implements VisibilityDriver
{
    public function redact(object $gameState, User $player): object
    {
        // Return the full state, no redaction needed.
        return $gameState;
    }
}
