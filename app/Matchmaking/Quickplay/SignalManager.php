<?php

declare(strict_types=1);

namespace App\Matchmaking\Quickplay;

use App\Enums\GameTitle;
use App\Models\Auth\User;
use App\Models\MatchmakingSignal;
use Carbon\Carbon;

/**
 * Manages matchmaking signal lifecycle (create, cancel, expire).
 */
class SignalManager
{
    /**
     * Create or update a matchmaking signal for a user.
     */
    public function createSignal(
        User $user,
        GameTitle $gameTitle,
        string $gameMode,
        int $clientId,
        array $preferences = [],
        ?int $skillRating = null
    ): MatchmakingSignal {
        $ttl = (int) config('protocol.floor.matchmaking.signal_ttl_minutes', 5);

        $preferences = array_merge(
            [
                'game_mode' => $gameMode,
                'client_id' => $clientId,
            ],
            $preferences
        );

        return MatchmakingSignal::updateOrCreate(
            ['user_id' => $user->id],
            [
                'game_preference' => $gameTitle->value,
                'skill_rating' => $skillRating,
                'status' => 'active',
                'preferences' => $preferences,
                'expires_at' => Carbon::now()->addMinutes($ttl),
            ]
        );
    }

    /**
     * Cancel an active signal for a user.
     */
    public function cancelSignal(User $user): ?MatchmakingSignal
    {
        $signal = MatchmakingSignal::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $signal) {
            return null;
        }

        $signal->update([
            'status' => 'cancelled',
            'expires_at' => Carbon::now(),
        ]);

        return $signal;
    }

    /**
     * Check if a signal has expired.
     */
    public function isExpired(MatchmakingSignal $signal): bool
    {
        return $signal->expires_at && $signal->expires_at->isPast();
    }
}
