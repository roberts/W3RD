<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\Visibility;

use App\GameEngine\Interfaces\VisibilityDriver;
use App\Models\Auth\User;

class AsymmetricInfoDriver implements VisibilityDriver
{
    public function redact(object $gameState, User $player): object
    {
        $redactedState = clone $gameState;
        $playerRole = $redactedState->players[$player->id]->role ?? null;

        // Redact based on the player's role.
        // e.g., an Imposter might see things Crewmates cannot.

        return $redactedState;
    }
}
