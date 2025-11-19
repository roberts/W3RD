<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Client\ResolveClientIdAction;
use App\Actions\Lobby\FindLobbyByUlidAction;
use App\Actions\User\ResolveUsernameAction;
use App\Enums\LobbyPlayerStatus;
use App\Enums\LobbyStatus;
use App\Enums\PlayerActivityState;
use App\Events\LobbyInvitation;
use App\Exceptions\LobbyStateException;
use App\Exceptions\PlayerBusyException;
use App\Http\Requests\Lobby\InvitePlayerRequest;
use App\Http\Requests\Lobby\RespondToInvitationRequest;
use App\Http\Traits\ApiResponses;
use App\Models\Auth\User;
use App\Models\Game\Lobby;
use App\Models\Game\LobbyPlayer;
use App\Services\GameCreationService;
use App\Services\PlayerActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LobbyPlayerController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected GameCreationService $gameCreationService,
        protected ResolveUsernameAction $resolveUsername,
        protected ResolveClientIdAction $resolveClientId,
        protected FindLobbyByUlidAction $findLobby
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Invite a player to a lobby (Host only)
     */
    public function store(InvitePlayerRequest $request, string $lobbyUlid): JsonResponse
    {
        $validated = $request->validated();

        $lobby = $this->findLobby->execute($lobbyUlid);
        $user = $request->user();

        if (! $lobby->isHost($user)) {
            return $this->forbiddenResponse('Only the host can invite players');
        }

        if ($lobby->status !== LobbyStatus::PENDING) {
            throw new LobbyStateException(
                'Cannot invite players to a non-pending lobby',
                $lobby->status->value,
                ['lobby_ulid' => $lobby->ulid]
            );
        }

        // Resolve username to user
        $invitee = $this->resolveUsername->execute($validated['username']);

        // Check if player is already in lobby
        $existing = LobbyPlayer::where('lobby_id', $lobby->id)
            ->where('user_id', $invitee->id)
            ->first();

        if ($existing) {
            throw new PlayerBusyException(
                "Player {$invitee->username} is already in this lobby",
                'in_lobby',
                ['lobby_ulid' => $lobby->ulid, 'username' => $invitee->username]
            );
        }

        // Create invitation
        $lobbyPlayer = LobbyPlayer::create([
            'lobby_id' => $lobby->id,
            'user_id' => $invitee->id,
            'status' => LobbyPlayerStatus::PENDING,
        ]);

        // Broadcast invitation
        broadcast(new LobbyInvitation($invitee->id, $lobby));

        return $this->createdResponse(null, 'Player invited successfully');
    }

    /**
     * Respond to a lobby invitation or join a public lobby
     */
    public function update(RespondToInvitationRequest $request, string $lobbyUlid, string $username): JsonResponse
    {
        $validated = $request->validated();

        $lobby = $this->findLobby->execute($lobbyUlid);
        $currentUser = $request->user();

        // Resolve username to user
        $user = $this->resolveUsername->execute($username);

        // Verify the user is responding for themselves
        if ($currentUser->id !== $user->id) {
            return $this->forbiddenResponse('You can only respond for yourself');
        }

        $lobbyPlayer = LobbyPlayer::where('lobby_id', $lobby->id)
            ->where('user_id', $user->id)
            ->first();

        // If no existing record and lobby is public, allow joining
        if (! $lobbyPlayer && $lobby->is_public && $validated['status'] === 'accepted') {
            $clientId = $this->resolveClientId->execute($request);

            $lobbyPlayer = LobbyPlayer::create([
                'lobby_id' => $lobby->id,
                'user_id' => $user->id,
                'client_id' => $clientId,
                'status' => LobbyPlayerStatus::ACCEPTED,
            ]);

            // Set player activity to IN_LOBBY
            $activityService = app(PlayerActivityService::class);
            $activityService->setState($user->id, PlayerActivityState::IN_LOBBY);

            // Check if we can start the game (exact player count for games that require it)
            if ($lobby->canStartGame() && ! $lobby->scheduled_at) {
                $this->startGame($lobby);
            }

            return $this->messageResponse('Successfully joined the lobby');
        }

        if (! $lobbyPlayer) {
            return $this->notFoundResponse('You are not invited to this lobby');
        }

        if ($lobbyPlayer->status !== LobbyPlayerStatus::PENDING) {
            throw new PlayerBusyException(
                'You have already responded to this invitation',
                'invitation_already_responded',
                ['lobby_ulid' => $lobby->ulid, 'status' => $lobbyPlayer->status->value]
            );
        }

        // Update status
        if ($validated['status'] === 'accepted') {
            $clientId = $this->resolveClientId->execute($request);

            $lobbyPlayer->update(['client_id' => $clientId]);
            $lobbyPlayer->accept();

            // Set player activity to IN_LOBBY
            $activityService = app(PlayerActivityService::class);
            $activityService->setState($user->id, PlayerActivityState::IN_LOBBY);

            // Check if we can start the game (exact player count for games that require it)
            if ($lobby->canStartGame() && ! $lobby->scheduled_at) {
                $this->startGame($lobby);
            }

            return $this->messageResponse('Invitation accepted');
        } else {
            $lobbyPlayer->decline();

            // Player declined, they never entered lobby so set to IDLE
            $activityService = app(PlayerActivityService::class);
            $activityService->setState($user->id, PlayerActivityState::IDLE);

            return $this->messageResponse('Invitation declined');
        }
    }

    /**
     * Kick a player from a lobby (Host only)
     */
    public function destroy(Request $request, string $lobbyUlid, string $username): JsonResponse
    {
        $lobby = $this->findLobby->execute($lobbyUlid);
        $currentUser = $request->user();

        if (! $lobby->isHost($currentUser)) {
            return $this->forbiddenResponse('Only the host can kick players');
        }

        // Resolve username to user
        $user = $this->resolveUsername->execute($username);

        if ($user->id === $currentUser->id) {
            throw new LobbyStateException(
                'Host cannot kick themselves from the lobby',
                $lobby->status->value,
                ['lobby_ulid' => $lobby->ulid]
            );
        }

        $lobbyPlayer = LobbyPlayer::where('lobby_id', $lobby->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $lobbyPlayer->delete();

        // Set kicked player activity to IDLE
        $activityService = app(PlayerActivityService::class);
        $activityService->setState($user->id, PlayerActivityState::IDLE);

        return $this->noContentResponse();
    }

    /**
     * Start the game when conditions are met
     */
    private function startGame(Lobby $lobby): void
    {
        if ($lobby->status !== LobbyStatus::PENDING) {
            return;
        }

        $lobby->markAsReady();

        // Each player has their own client_id stored in lobby_players table
        // GameCreationService will read from there
        $this->gameCreationService->createFromLobby($lobby);
    }
}
