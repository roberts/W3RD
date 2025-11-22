<?php

namespace App\Matchmaking\Lobby;

use App\Matchmaking\Enums\LobbyPlayerStatus;
use App\Matchmaking\Events\LobbyInvitation as LobbyInvitationEvent;
use App\Matchmaking\Events\LobbyPlayerJoined;
use App\Models\Auth\User;
use App\Models\Matchmaking\Lobby;
use App\Models\Matchmaking\LobbyPlayer;

/**
 * Manages lobby player operations: invitations, acceptances, kicking, seating
 */
class LobbyPlayerManager
{
    /**
     * Invite a player to a lobby
     */
    public function invitePlayer(Lobby $lobby, User $invitee): LobbyPlayer
    {
        $lobbyPlayer = LobbyPlayer::create([
            'lobby_id' => $lobby->id,
            'user_id' => $invitee->id,
            'status' => LobbyPlayerStatus::PENDING,
        ]);

        broadcast(new LobbyInvitationEvent($invitee->id, $lobby));

        return $lobbyPlayer;
    }

    /**
     * Invite multiple players to a lobby
     */
    public function inviteMultiplePlayers(Lobby $lobby, array $inviteeIds, int $hostId): void
    {
        foreach ($inviteeIds as $inviteeId) {
            if ($inviteeId === $hostId) {
                continue; // Skip host
            }

            $lobbyPlayer = LobbyPlayer::create([
                'lobby_id' => $lobby->id,
                'user_id' => $inviteeId,
                'status' => LobbyPlayerStatus::PENDING,
            ]);

            broadcast(new LobbyInvitationEvent($inviteeId, $lobby));
        }
    }

    /**
     * Accept an invitation
     */
    public function acceptInvitation(LobbyPlayer $lobbyPlayer, int $clientId, User $user, Lobby $lobby): void
    {
        $lobbyPlayer->update(['client_id' => $clientId]);
        $lobbyPlayer->accept();

        event(new LobbyPlayerJoined($lobby, $user));
    }

    /**
     * Decline an invitation
     */
    public function declineInvitation(LobbyPlayer $lobbyPlayer): void
    {
        $lobbyPlayer->decline();
    }

    /**
     * Join a public lobby
     */
    public function joinPublicLobby(Lobby $lobby, User $user, int $clientId): LobbyPlayer
    {
        $lobbyPlayer = LobbyPlayer::create([
            'lobby_id' => $lobby->id,
            'user_id' => $user->id,
            'client_id' => $clientId,
            'status' => LobbyPlayerStatus::ACCEPTED,
        ]);

        event(new LobbyPlayerJoined($lobby, $user));

        return $lobbyPlayer;
    }

    /**
     * Kick a player from a lobby
     */
    public function kickPlayer(LobbyPlayer $lobbyPlayer): void
    {
        $lobbyPlayer->delete();
    }

    /**
     * Check if a player exists in a lobby
     */
    public function findPlayerInLobby(Lobby $lobby, User $user): ?LobbyPlayer
    {
        return LobbyPlayer::where('lobby_id', $lobby->id)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Check if a player has already responded to invitation
     */
    public function hasAlreadyResponded(LobbyPlayer $lobbyPlayer): bool
    {
        return $lobbyPlayer->status !== LobbyPlayerStatus::PENDING;
    }
}
