<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\Visibility;

use App\GameEngine\Interfaces\VisibilityDriver;
use App\Models\Auth\User;

class HiddenInformationDriver implements VisibilityDriver
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
