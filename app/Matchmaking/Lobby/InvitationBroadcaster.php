<?php

namespace App\Matchmaking\Lobby;

use App\Matchmaking\Events\LobbyInvitation;
use App\Matchmaking\Events\LobbyReadyCheck;
use App\Models\Game\Lobby;

/**
 * Handles broadcasting of lobby-related events
 */
class InvitationBroadcaster
{
    /**
     * Broadcast a lobby invitation
     */
    public function broadcastInvitation(int $userId, Lobby $lobby): void
    {
        broadcast(new LobbyInvitation($userId, $lobby));
    }

    /**
     * Broadcast a ready check
     */
    public function broadcastReadyCheck(Lobby $lobby): void
    {
        broadcast(new LobbyReadyCheck($lobby));
    }
}
