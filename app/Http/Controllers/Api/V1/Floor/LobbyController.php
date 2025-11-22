<?php

namespace App\Http\Controllers\Api\V1\Floor;

use App\Actions\Client\ResolveClientIdAction;
use App\Actions\Lobby\FindLobbyByUlidAction;
use App\Actions\User\ResolveUsernameAction;
use App\Enums\GameTitle;
use App\Enums\LobbyPlayerStatus;
use App\Enums\LobbyStatus;
use App\Enums\PlayerActivityState;
use App\Events\Floor\LobbyPlayerJoined;
use App\Events\LobbyInvitation;
use App\Events\LobbyReadyCheck;
use App\Exceptions\InvalidGameConfigurationException;
use App\Exceptions\LobbyStateException;
use App\Exceptions\PlayerBusyException;
use App\Http\Requests\Lobby\CancelLobbyRequest;
use App\Http\Requests\Lobby\CreateLobbyRequest;
use App\Http\Requests\Lobby\InitiateReadyCheckRequest;
use App\Http\Requests\Lobby\InvitePlayerRequest;
use App\Http\Requests\Lobby\RespondToInvitationRequest;
use App\Http\Resources\LobbyResource;
use App\Http\Traits\ApiResponses;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Lobby;
use App\Models\Game\LobbyPlayer;
use App\GameEngine\Lifecycle\GameBuilder;
use App\GameEngine\Player\PlayerActivityManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class LobbyController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected ResolveClientIdAction $resolveClientId,
        protected FindLobbyByUlidAction $findLobby,
        protected ResolveUsernameAction $resolveUsername,
        protected GameBuilder $gameBuilder,
        protected PlayerActivityManager $playerActivityManager
    ) {}

    /**
     * List all public lobbies
     */
    public function index(Request $request): JsonResponse
    {
        $lobbies = Lobby::with(['host.avatar.image', 'players.user.avatar.image'])
            ->where('is_public', true)
            ->where('status', LobbyStatus::PENDING)
            ->latest()
            ->get();

        return $this->resourceResponse(LobbyResource::collection($lobbies));
    }

    /**
     * Create a new lobby
     */
    public function store(CreateLobbyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $gameTitle = GameTitle::fromSlug($validated['game_title']);

        if (! $gameTitle) {
            throw new InvalidGameConfigurationException(
                "Game title '{$validated['game_title']}' is not supported",
                $validated['game_title'],
                ['available_titles' => array_column(GameTitle::cases(), 'value')]
            );
        }

        DB::beginTransaction();

        $lobby = $this->handleServiceCall(
            function () use ($validated, $user, $request) {
                $lobby = Lobby::create([
                    'game_title' => GameTitle::fromSlug($validated['game_title']),
                    'game_mode' => $validated['game_mode'] ?? null,
                    'host_id' => $user->id,
                    'is_public' => $validated['is_public'] ?? false,
                    'min_players' => $validated['min_players'] ?? 2,
                    'scheduled_at' => $validated['scheduled_at'] ?? null,
                    'status' => LobbyStatus::PENDING,
                ]);

                // Add host as first player (auto-accepted, defaults to client_id 1 for AI agents)
                $clientId = $this->resolveClientId->execute($request);

                LobbyPlayer::create([
                    'lobby_id' => $lobby->id,
                    'user_id' => $user->id,
                    'client_id' => $clientId,
                    'status' => LobbyPlayerStatus::ACCEPTED,
                ]);

                $this->playerActivityManager->setState($user->id, PlayerActivityState::IN_LOBBY);

                // Add invitees
                if (! empty($validated['invitees'])) {
                    foreach ($validated['invitees'] as $inviteeId) {
                        if ($inviteeId === $user->id) {
                            continue; // Skip host
                        }

                        $lobbyPlayer = LobbyPlayer::create([
                            'lobby_id' => $lobby->id,
                            'user_id' => $inviteeId,
                            'status' => LobbyPlayerStatus::PENDING,
                        ]);

                        // Broadcast invitation
                        broadcast(new LobbyInvitation($inviteeId, $lobby));
                    }
                }

                DB::commit();

                return $lobby;
            },
            'Failed to create lobby'
        );

        if ($lobby instanceof JsonResponse) {
            DB::rollBack();

            return $lobby;
        }

        return $this->createdResourceResponse(
            LobbyResource::make($lobby),
            'Lobby created successfully'
        );
    }

    /**
     * Get lobby details
     */
    public function show(Request $request, string $lobbyUlid): JsonResponse
    {
        $lobby = $this->findLobby->execute($lobbyUlid, ['host.avatar.image', 'players.user.avatar.image']);

        $data = [
            'lobby' => LobbyResource::make($lobby),
        ];

        // Include game information if lobby is completed and has a game
        $game = $lobby->game;
        if ($game instanceof Game) {
            $data['game'] = [
                'ulid' => $game->ulid,
            ];
        }

        return $this->dataResponse($data);
    }

    /**
     * Cancel a lobby (Host only)
     */
    public function destroy(CancelLobbyRequest $request, string $lobbyUlid): JsonResponse
    {
        $lobby = $this->findLobby->execute($lobbyUlid);

        // Get all players before marking as cancelled
        $playerIds = $lobby->players()->pluck('user_id')->toArray();

        $lobby->markAsCancelled();

        // Set all players back to IDLE
        foreach ($playerIds as $playerId) {
            $this->playerActivityManager->setState($playerId, PlayerActivityState::IDLE);
        }

        return $this->noContentResponse();
    }

    /**
     * Initiate a ready check (Host only)
     */
    public function readyCheck(InitiateReadyCheckRequest $request, string $lobbyUlid): JsonResponse
    {
        $lobby = $this->findLobby->execute($lobbyUlid);

        // Broadcast ready check event
        broadcast(new LobbyReadyCheck($lobby));

        return $this->dataResponse(
            ['ready_check_initiated' => true],
            'Ready check initiated',
            202
        );
    }

    /**
     * Invite a player to a lobby (Host only)
     */
    public function invite(InvitePlayerRequest $request, string $lobbyUlid): JsonResponse
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
    public function respond(RespondToInvitationRequest $request, string $lobbyUlid, string $username): JsonResponse
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
            $this->playerActivityManager->setState($user->id, PlayerActivityState::IN_LOBBY);

            event(new LobbyPlayerJoined($lobby, $user));

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

            $this->playerActivityManager->setState($user->id, PlayerActivityState::IN_LOBBY);

            event(new LobbyPlayerJoined($lobby, $user));

            // Check if we can start the game (exact player count for games that require it)
            if ($lobby->canStartGame() && ! $lobby->scheduled_at) {
                $this->startGame($lobby);
            }

            return $this->messageResponse('Invitation accepted');
        } else {
            $lobbyPlayer->decline();

            $this->playerActivityManager->setState($user->id, PlayerActivityState::IDLE);

            return $this->messageResponse('Invitation declined');
        }
    }

    /**
     * Convenience endpoint for the authenticated player to take a seat in the lobby.
     */
    public function seat(RespondToInvitationRequest $request, string $lobbyUlid): JsonResponse
    {
        return $this->respond($request, $lobbyUlid, $request->user()->username);
    }

    /**
     * Kick a player from a lobby (Host only)
     */
    public function kick(Request $request, string $lobbyUlid, string $username): JsonResponse
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

        $this->playerActivityManager->setState($user->id, PlayerActivityState::IDLE);

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
        $this->gameBuilder->createFromLobby($lobby);
    }
}
