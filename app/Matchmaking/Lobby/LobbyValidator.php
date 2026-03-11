<?php

namespace App\Matchmaking\Lobby;

use App\Enums\GameTitle;
use App\Exceptions\InvalidGameConfigurationException;
use App\Exceptions\LobbyStateException;
use App\Exceptions\PlayerBusyException;
use App\Matchmaking\Enums\LobbyPlayerStatus;
use App\Matchmaking\Enums\LobbyStatus;
use App\Models\Auth\User;
use App\Models\Matchmaking\Lobby;
use App\Models\Matchmaking\LobbyPlayer;

/**
 * Validates lobby operations: capacity, status, permissions, player availability
 */
class LobbyValidator
{
    /**
     * Validate that a user is the lobby host
     */
    public function validateIsHost(Lobby $lobby, User $user): void
    {
        if (! $lobby->isHost($user)) {
            throw new LobbyStateException(
                'Only the host can perform this action',
                $lobby->status->value,
                ['lobby_ulid' => $lobby->ulid]
            );
        }
    }

    /**
     * Validate that lobby is in pending status
     */
    public function validateIsPending(Lobby $lobby): void
    {
        if ($lobby->status !== LobbyStatus::PENDING) {
            throw new LobbyStateException(
                'Lobby must be in pending status',
                $lobby->status->value,
                ['lobby_ulid' => $lobby->ulid]
            );
        }
    }

    /**
     * Validate that a player is not already in the lobby
     */
    public function validatePlayerNotInLobby(?LobbyPlayer $existingPlayer, string $username, string $lobbyUlid): void
    {
        if ($existingPlayer) {
            throw new PlayerBusyException(
                "Player {$username} is already in this lobby",
                'in_lobby',
                ['lobby_ulid' => $lobbyUlid, 'username' => $username]
            );
        }
    }

    /**
     * Validate that a player is responding for themselves
     */
    public function validateRespondingForSelf(User $currentUser, User $targetUser): void
    {
        if ($currentUser->id !== $targetUser->id) {
            throw new LobbyStateException(
                'You can only respond for yourself',
                'unauthorized',
                ['current_user_id' => $currentUser->id, 'target_user_id' => $targetUser->id]
            );
        }
    }

    /**
     * Validate that a player has not already responded to invitation
     */
    public function validateNotAlreadyResponded(LobbyPlayer $lobbyPlayer): void
    {
        if ($lobbyPlayer->status !== LobbyPlayerStatus::PENDING) {
            /** @var Lobby $lobby */
            $lobby = $lobbyPlayer->lobby;
            throw new PlayerBusyException(
                'You have already responded to this invitation',
                'invitation_already_responded',
                ['lobby_ulid' => $lobby->ulid, 'status' => $lobbyPlayer->status->value]
            );
        }
    }

    /**
     * Validate that host cannot kick themselves
     */
    public function validateNotKickingSelf(User $host, User $target, Lobby $lobby): void
    {
        if ($target->id === $host->id) {
            throw new LobbyStateException(
                'Host cannot kick themselves from the lobby',
                $lobby->status->value,
                ['lobby_ulid' => $lobby->ulid]
            );
        }
    }

    /**
     * Validate that a game title is supported
     */
    public function validateGameTitle(?object $gameTitle, string $slug): void
    {
        if (! $gameTitle) {
            throw new InvalidGameConfigurationException(
                "Game title '{$slug}' is not supported",
                $slug,
                ['available_titles' => array_column(GameTitle::cases(), 'value')]
            );
        }
    }
}
