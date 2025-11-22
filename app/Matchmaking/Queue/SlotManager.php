<?php

declare(strict_types=1);

namespace App\Matchmaking\Queue;

use App\Enums\GameTitle;
use App\Models\Auth\User;
use App\Models\Matchmaking\QueueSlot;
use Carbon\Carbon;

/**
 * Manages queue slot lifecycle (create, cancel, expire).
 */
class SlotManager
{
    /**
     * Create or update a queue slot for a user.
     */
    public function createSlot(
        User $user,
        GameTitle $gameTitle,
        string $gameMode,
        int $modeId,
        int $clientId,
        array $preferences = [],
        ?int $skillRating = null
    ): QueueSlot {
        $ttl = (int) config('protocol.floor.matchmaking.signal_ttl_minutes', 5);

        $preferences = array_merge(
            [
                'game_mode' => $gameMode,
                'client_id' => $clientId,
            ],
            $preferences
        );

        return QueueSlot::updateOrCreate(
            ['user_id' => $user->id],
            [
                'title_slug' => $gameTitle->value,
                'mode_id' => $modeId,
                'skill_rating' => $skillRating,
                'status' => 'active',
                'preferences' => $preferences,
                'expires_at' => Carbon::now()->addMinutes($ttl),
            ]
        );
    }

    /**
     * Cancel an active slot for a user.
     */
    public function cancelSlot(User $user): ?QueueSlot
    {
        $slot = QueueSlot::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $slot) {
            return null;
        }

        $slot->update([
            'status' => 'cancelled',
            'expires_at' => Carbon::now(),
        ]);

        return $slot;
    }

    /**
     * Check if a slot has expired.
     */
    public function isExpired(QueueSlot $slot): bool
    {
        return $slot->expires_at && $slot->expires_at->isPast();
    }
}
