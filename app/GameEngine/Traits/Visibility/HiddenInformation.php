<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\Visibility;

use App\Models\Auth\User;

/**
 * Hidden information visibility.
 * Players have private information (hands, resources) hidden from opponents.
 * 
 * Examples: Poker, Hearts, Uno, most card games
 */
trait HiddenInformation
{
    public function redact(object $gameState, User $player): object
    {
        $redactedState = clone $gameState;

        foreach ($redactedState->players as $playerState) {
            if ($playerState->id !== $player->id) {
                // Redact opponent's hand
                if (isset($playerState->hand)) {
                    $playerState->hand_count = count($playerState->hand);
                    unset($playerState->hand);
                }
                // Add other redactions as needed
            }
        }

        return $redactedState;
    }
}
