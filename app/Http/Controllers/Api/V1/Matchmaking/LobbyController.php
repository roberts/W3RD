<?php

namespace App\Http\Controllers\Api\V1\Matchmaking;

use App\Http\Requests\Matchmaking\CancelLobbyRequest;
use App\Http\Requests\Matchmaking\CreateLobbyRequest;
use App\Http\Requests\Matchmaking\InitiateReadyCheckRequest;
use App\Http\Requests\Matchmaking\InvitePlayerRequest;
use App\Http\Requests\Matchmaking\KickPlayerRequest;
use App\Http\Requests\Matchmaking\ListLobbiesRequest;
use App\Http\Requests\Matchmaking\RespondToInvitationRequest;
use App\Http\Resources\LobbyResource;
use App\Http\Traits\ApiResponses;
use App\Matchmaking\Orchestrators\LobbyOrchestrator;
use App\Models\Games\Game;
use App\Models\Matchmaking\Lobby;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LobbyController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected LobbyOrchestrator $lobbyOrchestrator
    ) {}

    /**
     * List all public lobbies
     */
    public function index(ListLobbiesRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Lobby::with(['host.avatar.image', 'players.user.avatar.image']);

        // Apply filters
        if (isset($validated['is_public'])) {
            $query->where('is_public', $validated['is_public']);
        } else {
            $query->where('is_public', true);
        }

        if (isset($validated['game_title'])) {
            $query->whereHas('mode', function ($q) use ($validated) {
                $q->where('title_slug', $validated['game_title']);
            });
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        } else {
            $query->pending();
        }

        $lobbies = $query->latest()->get();

        return $this->resourceResponse(LobbyResource::collection($lobbies));
    }

    /**
     * Create a new lobby
     */
    public function store(CreateLobbyRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $result = $this->lobbyOrchestrator->createLobby(
            $user,
            $validated['game_title'],
            $validated['mode_id'],
            $validated['is_public'] ?? false,
            $validated['min_players'] ?? 2,
            $validated['scheduled_at'] ?? null,
            $validated['invitees'] ?? [],
            $request
        );

        if (! $result->success) {
            return $this->errorResponse($result->errorMessage, 422);
        }

        return $this->createdResourceResponse(
            LobbyResource::make($result->lobby),
            'Lobby created successfully'
        );
    }

    /**
     * Get lobby details
     */
    public function show(Request $request, string $lobbyUlid): JsonResponse
    {
        $lobby = Lobby::withUlid($lobbyUlid, ['host.avatar.image', 'players.user.avatar.image'])->firstOrFail();

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
        $lobby = Lobby::withUlid($lobbyUlid)->firstOrFail();

        $result = $this->lobbyOrchestrator->cancelLobby($lobby, $request->user());

        if (! $result->success) {
            return $this->errorResponse($result->errorMessage, 422);
        }

        return $this->noContentResponse();
    }

    /**
     * Initiate a ready check (Host only)
     */
    public function readyCheck(InitiateReadyCheckRequest $request, string $lobbyUlid): JsonResponse
    {
        $lobby = Lobby::withUlid($lobbyUlid)->firstOrFail();

        $result = $this->lobbyOrchestrator->initiateReadyCheck($lobby, $request->user());

        if (! $result->success) {
            return $this->errorResponse($result->errorMessage, 422);
        }

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
        $lobby = Lobby::withUlid($lobbyUlid)->firstOrFail();

        $result = $this->lobbyOrchestrator->invitePlayer($lobby, $request->user(), $validated['username']);

        if (! $result->success) {
            $statusCode = str_contains($result->errorMessage, 'Only the host') ? 403 : 422;

            return $this->errorResponse($result->errorMessage, $statusCode);
        }

        return $this->createdResponse(null, 'Player invited successfully');
    }

    /**
     * Respond to a lobby invitation or join a public lobby
     */
    public function respond(RespondToInvitationRequest $request, string $lobbyUlid, string $username): JsonResponse
    {
        $validated = $request->validated();
        $lobby = Lobby::withUlid($lobbyUlid)->firstOrFail();

        if ($validated['status'] === 'accepted') {
            $result = $this->lobbyOrchestrator->acceptInvitationOrJoin($lobby, $request->user(), $username, $request);
        } else {
            $result = $this->lobbyOrchestrator->declineInvitation($lobby, $request->user(), $username);
        }

        if (! $result->success) {
            $statusCode = $result->errorMessage === 'You are not invited to this lobby' ? 404 : 422;

            // Check for "already in lobby" error which should be 409
            if (str_contains($result->errorMessage, 'already')) {
                $statusCode = 409;
            }

            return $this->errorResponse($result->errorMessage, $statusCode);
        }

        return $this->messageResponse($result->message ?? 'Response recorded');
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
    public function kick(KickPlayerRequest $request, string $lobbyUlid, string $username): JsonResponse
    {
        $lobby = Lobby::withUlid($lobbyUlid)->firstOrFail();

        $result = $this->lobbyOrchestrator->kickPlayer($lobby, $request->user(), $username);

        if (! $result->success) {
            if ($result->errorMessage === 'Player not found in lobby') {
                return $this->errorResponse($result->errorMessage, 404);
            }

            $statusCode = str_contains($result->errorMessage, 'Only the host') ? 403 : 422;

            return $this->errorResponse($result->errorMessage, $statusCode);
        }

        return $this->noContentResponse();
    }
}
