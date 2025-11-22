<?php

namespace App\Matchmaking\Lobby;

use App\Enums\GameTitle;
use App\Matchmaking\Enums\LobbyPlayerStatus;
use App\Matchmaking\Enums\LobbyStatus;
use App\Models\Auth\User;
use App\Models\Game\Lobby;
use App\Models\Game\LobbyPlayer;

/**
 * Manages core lobby operations: creation, cancellation, status updates
 */
class LobbyManager
{
    /**
     * Create a new lobby
     */
    public function createLobby(
        User $host,
        GameTitle $gameTitle,
        ?string $gameMode,
        bool $isPublic,
        int $minPlayers,
        ?string $scheduledAt,
        int $clientId
    ): Lobby {
        $lobby = Lobby::create([
            'game_title' => $gameTitle,
            'game_mode' => $gameMode,
            'host_id' => $host->id,
            'is_public' => $isPublic,
            'min_players' => $minPlayers,
            'scheduled_at' => $scheduledAt,
            'status' => LobbyStatus::PENDING,
        ]);

        // Add host as first player (auto-accepted)
        LobbyPlayer::create([
            'lobby_id' => $lobby->id,
            'user_id' => $host->id,
            'client_id' => $clientId,
            'status' => LobbyPlayerStatus::ACCEPTED,
        ]);

        return $lobby->fresh(['host', 'players.user']);
    }

    /**
     * Cancel a lobby
     */
    public function cancelLobby(Lobby $lobby): void
    {
        $lobby->markAsCancelled();
    }

    /**
     * Mark lobby as ready to start game
     */
    public function markLobbyReady(Lobby $lobby): void
    {
        if ($lobby->status !== LobbyStatus::PENDING) {
            return;
        }

        $lobby->markAsReady();
    }

    /**
     * Get all player IDs in a lobby
     */
    public function getPlayerIds(Lobby $lobby): array
    {
        return $lobby->players()->pluck('user_id')->toArray();
    }

    /**
     * Check if lobby can start game
     */
    public function canStartGame(Lobby $lobby): bool
    {
        return $lobby->canStartGame() && ! $lobby->scheduled_at;
    }
}
