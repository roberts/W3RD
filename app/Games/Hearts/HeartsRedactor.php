<?php

namespace App\Games\Hearts;

use App\GameEngine\Interfaces\GameRedactor;
use App\Models\Game\Game;
use App\Models\Auth\User;

class HeartsRedactor implements GameRedactor
{
    public function redact(Game $game, ?User $user): array
    {
        $state = $game->game_state ?? [];

        if (! $user) {
            // If there's no user, redact all hands
            foreach ($state['players'] as &$player) {
                unset($player['hand']);
            }

            return $state;
        }

        // Redact the hands of other players
        foreach ($state['players'] as &$player) {
            if ($player['id'] !== $user->id) {
                unset($player['hand']);
            }
        }

        return $state;
    }
}
