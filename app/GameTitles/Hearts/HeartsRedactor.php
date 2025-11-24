<?php

namespace App\GameTitles\Hearts;

use App\GameEngine\Interfaces\GameRedactor;
use App\Models\Auth\User;
use App\Models\Games\Game;

class HeartsRedactor implements GameRedactor
{
    /**
     * @return array<string, mixed>
     */
    public function redact(Game $game, ?User $user): array
    {
        $state = $game->game_state ?? [];

        if (! $user) {
            // If there's no user, redact all hands in state
            if (isset($state['hands'])) {
                $state['hands'] = array_map(fn ($hand) => [], $state['hands']);
            }

            return $state;
        }

        // Find the player ULID for this user
        $playerUlid = $game->players()->where('user_id', $user->id)->value('ulid');

        if (! $playerUlid) {
            // User is not a player in this game, redact all hands
            if (isset($state['hands'])) {
                $state['hands'] = array_map(fn ($hand) => [], $state['hands']);
            }

            return $state;
        }

        // Redact the hands of other players (keep only the current user's hand)
        if (isset($state['hands'])) {
            foreach ($state['hands'] as $ulid => &$hand) {
                if ($ulid !== $playerUlid) {
                    $hand = [];
                }
            }
        }

        return $state;
    }
}
