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
use App\Services\Matchmaking\LobbyQueryService;
use App\Services\Matchmaking\LobbyResponseMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LobbyController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected LobbyOrchestrator $lobbyOrchestrator,
        protected LobbyResponseMapper $responseMapper,
        protected LobbyQueryService $lobbyQueryService
    ) {}

    /**
     * List all public lobbies
     */
    public function index(ListLobbiesRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $lobbies = $this->lobbyQueryService
            ->buildLobbyQuery($validated)
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
            return $this->responseMapper->mapResultToResponse($result);
        }

        return $this->createdResourceResponse(
            LobbyResource::make($result->lobby),
            'Lobby created successfully'
        );
    }

    /**
     * Get lobby details
     */
    public function show(Request $request, Lobby $lobby): JsonResponse
    {
        $lobby->load(['host.avatar.image', 'players.user.avatar.image']);

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
    public function destroy(CancelLobbyRequest $request, Lobby $lobby): JsonResponse
    {

        $result = $this->lobbyOrchestrator->cancelLobby($lobby, $request->user());

        return $this->responseMapper->mapResultToResponse($result, null, 204);
    }

    /**
     * Initiate a ready check (Host only)
     */
    public function readyCheck(InitiateReadyCheckRequest $request, Lobby $lobby): JsonResponse
    {

        $result = $this->lobbyOrchestrator->initiateReadyCheck($lobby, $request->user());

        if (! $result->success) {
            return $this->responseMapper->mapResultToResponse($result);
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
    public function invite(InvitePlayerRequest $request, Lobby $lobby): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->lobbyOrchestrator->invitePlayer($lobby, $request->user(), $validated['username']);

        if (! $result->success) {
            return $this->responseMapper->mapResultToResponse($result);
        }

        return $this->createdResponse(null, 'Player invited successfully');
    }

    /**
     * Respond to a lobby invitation or join a public lobby
     */
    public function respond(RespondToInvitationRequest $request, Lobby $lobby, string $username): JsonResponse
    {
        $validated = $request->validated();

        if ($validated['status'] === 'accepted') {
            $result = $this->lobbyOrchestrator->acceptInvitationOrJoin($lobby, $request->user(), $username, $request);
        } else {
            $result = $this->lobbyOrchestrator->declineInvitation($lobby, $request->user(), $username);
        }

        return $this->responseMapper->mapResultToResponse($result, $result->message ?? 'Response recorded');
    }

    /**
     * Convenience endpoint for the authenticated player to take a seat in the lobby.
     */
    public function seat(RespondToInvitationRequest $request, Lobby $lobby): JsonResponse
    {
        return $this->respond($request, $lobby, $request->user()->username);
    }

    /**
     * Kick a player from a lobby (Host only)
     */
    public function kick(KickPlayerRequest $request, Lobby $lobby, string $username): JsonResponse
    {

        $result = $this->lobbyOrchestrator->kickPlayer($lobby, $request->user(), $username);

        return $this->responseMapper->mapResultToResponse($result, null, 204);
    }
}
