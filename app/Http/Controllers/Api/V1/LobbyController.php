<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GameTitle;
use App\Enums\LobbyPlayerStatus;
use App\Enums\LobbyStatus;
use App\Events\LobbyInvitation;
use App\Events\LobbyReadyCheck;
use App\Http\Requests\Lobby\CancelLobbyRequest;
use App\Http\Requests\Lobby\CreateLobbyRequest;
use App\Http\Requests\Lobby\InitiateReadyCheckRequest;
use App\Http\Resources\LobbyResource;
use App\Http\Traits\ApiResponses;
use App\Models\Game\Game;
use App\Models\Game\Lobby;
use App\Models\Game\LobbyPlayer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class LobbyController extends Controller
{
    use ApiResponses;

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
            return $this->errorResponse('Invalid game title');
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
                $clientId = (int) $request->header('X-Client-Key') ?: 1;

                LobbyPlayer::create([
                    'lobby_id' => $lobby->id,
                    'user_id' => $user->id,
                    'client_id' => $clientId,
                    'status' => LobbyPlayerStatus::ACCEPTED,
                ]);

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
        $lobby = Lobby::with(['host.avatar.image', 'players.user.avatar.image'])
            ->where('ulid', $lobbyUlid)
            ->firstOrFail();

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
        $lobby = Lobby::where('ulid', $lobbyUlid)->firstOrFail();

        $lobby->markAsCancelled();

        return $this->noContentResponse();
    }

    /**
     * Initiate a ready check (Host only)
     */
    public function readyCheck(InitiateReadyCheckRequest $request, string $lobbyUlid): JsonResponse
    {
        $lobby = Lobby::where('ulid', $lobbyUlid)->firstOrFail();

        // Broadcast ready check event
        broadcast(new LobbyReadyCheck($lobby));

        return $this->dataResponse(
            ['ready_check_initiated' => true],
            'Ready check initiated',
            202
        );
    }
}
