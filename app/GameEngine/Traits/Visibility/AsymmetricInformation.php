<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\Visibility;

use App\Models\Auth\User;

/**
 * Asymmetric information visibility.
 * Different players/roles see different information based on their role.
 * 
 * Examples: Among Us (impostor sees different info), Werewolf, Secret Hitler
 */
trait AsymmetricInformation
{
    public function redact(object $gameState, User $player): object
    {
        $redactedState = clone $gameState;
        $playerRole = $redactedState->players[$player->id]->role ?? null;

        // Redact based on the player's role.
        // e.g., an Impostor might see things Crewmates cannot.
        // Override this method in game implementation with role-specific logic

        return $redactedState;
    }
}
