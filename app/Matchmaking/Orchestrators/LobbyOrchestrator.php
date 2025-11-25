<?php

namespace App\Matchmaking\Orchestrators;

use App\Actions\Client\ResolveClientIdAction;
use App\Enums\GameTitle;
use App\Enums\PlayerActivityState;
use App\GameEngine\Player\PlayerActivityManager;
use App\Matchmaking\Lobby\InvitationBroadcaster;
use App\Matchmaking\Lobby\LobbyGameStarter;
use App\Matchmaking\Lobby\LobbyManager;
use App\Matchmaking\Lobby\LobbyPlayerManager;
use App\Matchmaking\Lobby\LobbyValidator;
use App\Matchmaking\Results\LobbyOperationResult;
use App\Models\Auth\User;
use App\Models\Matchmaking\Lobby;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates full lobby lifecycle: creation, invitations, joining, kicking, starting games
 */
class LobbyOrchestrator
{
    public function __construct(
        protected LobbyManager $lobbyManager,
        protected LobbyPlayerManager $lobbyPlayerManager,
        protected LobbyValidator $lobbyValidator,
        protected LobbyGameStarter $lobbyGameStarter,
        protected InvitationBroadcaster $invitationBroadcaster,
        protected PlayerActivityManager $playerActivityManager,
        protected ResolveClientIdAction $resolveClientId
    ) {}

    /**
     * Create a new lobby
     *
     * @param  array<int>  $inviteeIds
     */
    public function createLobby(
        User $host,
        string $gameTitleSlug,
        int $modeId,
        bool $isPublic,
        int $minPlayers,
        ?string $scheduledAt,
        array $inviteeIds,
        Request $request
    ): LobbyOperationResult {
        $gameTitle = GameTitle::fromSlug($gameTitleSlug);
        $this->lobbyValidator->validateGameTitle($gameTitle, $gameTitleSlug);

        DB::beginTransaction();

        try {
            $clientId = $this->resolveClientId->execute($request);

            $lobby = $this->lobbyManager->createLobby(
                $host,
                $gameTitle,
                $modeId,
                $isPublic,
                $minPlayers,
                $scheduledAt,
                $clientId
            );

            $this->playerActivityManager->setState($host->id, PlayerActivityState::IN_LOBBY);

            // Add invitees
            if (! empty($inviteeIds)) {
                $this->lobbyPlayerManager->inviteMultiplePlayers($lobby, $inviteeIds, $host->id);
            }

            DB::commit();

            return LobbyOperationResult::success($lobby);
        } catch (\Exception $e) {
            DB::rollBack();

            return LobbyOperationResult::failed($e->getMessage());
        }
    }

    /**
     * Cancel a lobby (Host only)
     */
    public function cancelLobby(Lobby $lobby, User $user): LobbyOperationResult
    {
        try {
            $this->lobbyValidator->validateIsHost($lobby, $user);

            $playerIds = $this->lobbyManager->getPlayerIds($lobby);

            $this->lobbyManager->cancelLobby($lobby);

            // Set all players back to IDLE
            foreach ($playerIds as $playerId) {
                $this->playerActivityManager->setState($playerId, PlayerActivityState::IDLE);
            }

            return LobbyOperationResult::success($lobby);
        } catch (\Exception $e) {
            return LobbyOperationResult::failed($e->getMessage());
        }
    }

    /**
     * Invite a player to a lobby (Host only)
     */
    public function invitePlayer(Lobby $lobby, User $host, string $username): LobbyOperationResult
    {
        try {
            $this->lobbyValidator->validateIsHost($lobby, $host);
            $this->lobbyValidator->validateIsPending($lobby);

            $invitee = User::withUsername($username)->firstOrFail();

            $existing = $this->lobbyPlayerManager->findPlayerInLobby($lobby, $invitee);
            $this->lobbyValidator->validatePlayerNotInLobby($existing, $invitee->username, $lobby->ulid);

            $this->lobbyPlayerManager->invitePlayer($lobby, $invitee);

            return LobbyOperationResult::success($lobby);
        } catch (\Exception $e) {
            return LobbyOperationResult::failed($e->getMessage());
        }
    }

    /**
     * Accept an invitation or join a public lobby
     */
    public function acceptInvitationOrJoin(
        Lobby $lobby,
        User $currentUser,
        string $username,
        Request $request
    ): LobbyOperationResult {
        try {
            $user = User::withUsername($username)->firstOrFail();
            $this->lobbyValidator->validateRespondingForSelf($currentUser, $user);

            $lobbyPlayer = $this->lobbyPlayerManager->findPlayerInLobby($lobby, $user);

            // If no existing record and lobby is public, allow joining
            if (! $lobbyPlayer && $lobby->is_public) {
                $clientId = $this->resolveClientId->execute($request);

                $this->lobbyPlayerManager->joinPublicLobby($lobby, $user, $clientId);
                $this->playerActivityManager->setState($user->id, PlayerActivityState::IN_LOBBY);

                // Check if we can start the game
                if ($this->lobbyManager->canStartGame($lobby)) {
                    $this->lobbyManager->markLobbyReady($lobby);
                    $this->lobbyGameStarter->startGame($lobby);
                }

                return LobbyOperationResult::success($lobby, 'Successfully joined the lobby');
            }

            if (! $lobbyPlayer) {
                return LobbyOperationResult::failed('You are not invited to this lobby');
            }

            $this->lobbyValidator->validateNotAlreadyResponded($lobbyPlayer);

            $clientId = $this->resolveClientId->execute($request);

            $this->lobbyPlayerManager->acceptInvitation($lobbyPlayer, $clientId, $user, $lobby);
            $this->playerActivityManager->setState($user->id, PlayerActivityState::IN_LOBBY);

            // Check if we can start the game
            if ($this->lobbyManager->canStartGame($lobby)) {
                $this->lobbyManager->markLobbyReady($lobby);
                $this->lobbyGameStarter->startGame($lobby);
            }

            return LobbyOperationResult::success($lobby, 'Invitation accepted');
        } catch (\Exception $e) {
            return LobbyOperationResult::failed($e->getMessage());
        }
    }

    /**
     * Decline an invitation
     */
    public function declineInvitation(Lobby $lobby, User $currentUser, string $username): LobbyOperationResult
    {
        try {
            $user = User::withUsername($username)->firstOrFail();
            $this->lobbyValidator->validateRespondingForSelf($currentUser, $user);

            $lobbyPlayer = $this->lobbyPlayerManager->findPlayerInLobby($lobby, $user);

            if (! $lobbyPlayer) {
                return LobbyOperationResult::failed('You are not invited to this lobby');
            }

            $this->lobbyValidator->validateNotAlreadyResponded($lobbyPlayer);

            $this->lobbyPlayerManager->declineInvitation($lobbyPlayer);
            $this->playerActivityManager->setState($user->id, PlayerActivityState::IDLE);

            return LobbyOperationResult::success($lobby, 'Invitation declined');
        } catch (\Exception $e) {
            return LobbyOperationResult::failed($e->getMessage());
        }
    }

    /**
     * Kick a player from a lobby (Host only)
     */
    public function kickPlayer(Lobby $lobby, User $host, string $username): LobbyOperationResult
    {
        try {
            $this->lobbyValidator->validateIsHost($lobby, $host);

            $user = User::withUsername($username)->firstOrFail();
            $this->lobbyValidator->validateNotKickingSelf($host, $user, $lobby);

            $lobbyPlayer = $this->lobbyPlayerManager->findPlayerInLobby($lobby, $user);

            if (! $lobbyPlayer) {
                return LobbyOperationResult::failed('Player not found in lobby');
            }

            $this->lobbyPlayerManager->kickPlayer($lobbyPlayer);
            $this->playerActivityManager->setState($user->id, PlayerActivityState::IDLE);

            return LobbyOperationResult::success($lobby);
        } catch (\Exception $e) {
            return LobbyOperationResult::failed($e->getMessage());
        }
    }

    /**
     * Initiate a ready check (Host only)
     */
    public function initiateReadyCheck(Lobby $lobby, User $host): LobbyOperationResult
    {
        try {
            $this->lobbyValidator->validateIsHost($lobby, $host);

            $this->invitationBroadcaster->broadcastReadyCheck($lobby);

            return LobbyOperationResult::success($lobby, 'Ready check initiated');
        } catch (\Exception $e) {
            return LobbyOperationResult::failed($e->getMessage());
        }
    }
}
