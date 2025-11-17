<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GameTitle;
use App\Enums\LobbyPlayerStatus;
use App\Enums\LobbyStatus;
use App\Events\LobbyInvitation;
use App\Events\LobbyReadyCheck;
use App\Models\Lobby;
use App\Models\LobbyPlayer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class LobbyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List all public lobbies
     */
    public function index(Request $request): JsonResponse
    {
        $lobbies = Lobby::with(['host', 'players.user'])
            ->where('is_public', true)
            ->where('status', LobbyStatus::PENDING)
            ->latest()
            ->get()
            ->map(function (Lobby $lobby) {
                /** @var \App\Models\Auth\User $host */
                $host = $lobby->host;

                return [
                    'ulid' => $lobby->ulid,
                    'game_title' => $lobby->game_title->value,
                    'game_mode' => $lobby->game_mode,
                    'host' => [
                        'id' => $host->id,
                        'name' => $host->name,
                        'username' => $host->username,
                    ],
                    'min_players' => $lobby->min_players,
                    'current_players' => $lobby->acceptedPlayers()->count(),
                    'scheduled_at' => $lobby->scheduled_at?->toIso8601String(),
                    'status' => $lobby->status->value,
                ];
            });

        return response()->json(['lobbies' => $lobbies]);
    }

    /**
     * Create a new lobby
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_title' => 'required|string',
            'game_mode' => 'nullable|string',
            'is_public' => 'boolean',
            'min_players' => 'integer|min:2|max:8',
            'scheduled_at' => 'nullable|date|after:now',
            'invitees' => 'array',
            'invitees.*' => 'integer|exists:users,id',
        ]);

        $user = $request->user();
        $gameTitle = GameTitle::fromSlug($validated['game_title']);

        if (! $gameTitle) {
            return response()->json(['error' => 'Invalid game title'], 400);
        }

        DB::beginTransaction();
        try {
            $lobby = Lobby::create([
                'game_title' => $gameTitle,
                'game_mode' => $validated['game_mode'] ?? null,
                'host_id' => $user->id,
                'is_public' => $validated['is_public'] ?? false,
                'min_players' => $validated['min_players'] ?? 2,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'status' => LobbyStatus::PENDING,
            ]);

            // Add host as first player (auto-accepted)
            LobbyPlayer::create([
                'lobby_id' => $lobby->id,
                'user_id' => $user->id,
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

            return response()->json([
                'message' => 'Lobby created successfully',
                'lobby' => [
                    'ulid' => $lobby->ulid,
                    'game_title' => $lobby->game_title->value,
                    'game_mode' => $lobby->game_mode,
                    'is_public' => $lobby->is_public,
                    'min_players' => $lobby->min_players,
                    'scheduled_at' => $lobby->scheduled_at?->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to create lobby'], 500);
        }
    }

    /**
     * Get lobby details
     */
    public function show(Request $request, string $lobbyUlid): JsonResponse
    {
        $lobby = Lobby::with(['host', 'players.user'])
            ->where('ulid', $lobbyUlid)
            ->firstOrFail();

        /** @var \App\Models\Auth\User $host */
        $host = $lobby->host;

        return response()->json([
            'lobby' => [
                'ulid' => $lobby->ulid,
                'game_title' => $lobby->game_title->value,
                'game_mode' => $lobby->game_mode,
                'host' => [
                    'id' => $host->id,
                    'name' => $host->name,
                    'username' => $host->username,
                ],
                'is_public' => $lobby->is_public,
                'min_players' => $lobby->min_players,
                'scheduled_at' => $lobby->scheduled_at?->toIso8601String(),
                'status' => $lobby->status->value,
                'players' => (function () use ($lobby) {
                    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\LobbyPlayer> $players */
                    $players = $lobby->players;

                    /** @phpstan-ignore-next-line */
                    return $players->map(function (LobbyPlayer $player) {
                        /** @var \App\Models\Auth\User $user */
                        $user = $player->user;

                        return [
                            'user' => [
                                'id' => $user->id,
                                'name' => $user->name,
                                'username' => $user->username,
                            ],
                            'status' => $player->status->value,
                        ];
                    });
                })(),
            ],
        ]);
    }

    /**
     * Cancel a lobby (Host only)
     */
    public function destroy(Request $request, string $lobbyUlid): JsonResponse
    {
        $lobby = Lobby::where('ulid', $lobbyUlid)->firstOrFail();
        $user = $request->user();

        if (! $lobby->isHost($user)) {
            return response()->json(['error' => 'Only the host can cancel the lobby'], 403);
        }

        if ($lobby->status !== LobbyStatus::PENDING) {
            return response()->json(['error' => 'Cannot cancel a lobby that is not pending'], 400);
        }

        $lobby->markAsCancelled();

        return response()->json(null, 204);
    }

    /**
     * Initiate a ready check (Host only)
     */
    public function readyCheck(Request $request, string $lobbyUlid): JsonResponse
    {
        $lobby = Lobby::where('ulid', $lobbyUlid)->firstOrFail();
        $user = $request->user();

        if (! $lobby->isHost($user)) {
            return response()->json(['error' => 'Only the host can initiate a ready check'], 403);
        }

        if ($lobby->status !== LobbyStatus::PENDING) {
            return response()->json(['error' => 'Lobby must be pending to initiate ready check'], 400);
        }

        // Broadcast ready check event
        broadcast(new LobbyReadyCheck($lobby));

        return response()->json([
            'message' => 'Ready check initiated',
        ], 202);
    }
}
