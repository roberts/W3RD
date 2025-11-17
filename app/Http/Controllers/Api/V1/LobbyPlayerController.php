<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LobbyPlayerStatus;
use App\Enums\LobbyStatus;
use App\Events\LobbyInvitation;
use App\Http\Requests\Lobby\InvitePlayerRequest;
use App\Http\Requests\Lobby\RespondToInvitationRequest;
use App\Models\Auth\User;
use App\Models\Game\Lobby;
use App\Models\Game\LobbyPlayer;
use App\Services\GameCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LobbyPlayerController extends Controller
{
    public function __construct(
        protected GameCreationService $gameCreationService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Invite a player to a lobby (Host only)
     */
    public function store(InvitePlayerRequest $request, string $lobbyUlid): JsonResponse
    {
        $validated = $request->validated();

        $lobby = Lobby::where('ulid', $lobbyUlid)->firstOrFail();
        $user = $request->user();

        if (! $lobby->isHost($user)) {
            return response()->json(['error' => 'Only the host can invite players'], 403);
        }

        if ($lobby->status !== LobbyStatus::PENDING) {
            return response()->json(['error' => 'Cannot invite players to a non-pending lobby'], 400);
        }

        // Resolve username to user
        $invitee = User::where('username', $validated['username'])->firstOrFail();

        // Check if player is already in lobby
        $existing = LobbyPlayer::where('lobby_id', $lobby->id)
            ->where('user_id', $invitee->id)
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Player is already in this lobby'], 400);
        }

        // Create invitation
        $lobbyPlayer = LobbyPlayer::create([
            'lobby_id' => $lobby->id,
            'user_id' => $invitee->id,
            'status' => LobbyPlayerStatus::PENDING,
        ]);

        // Broadcast invitation
        broadcast(new LobbyInvitation($invitee->id, $lobby));

        return response()->json([
            'message' => 'Player invited successfully',
        ], 201);
    }

    /**
     * Respond to a lobby invitation or join a public lobby
     */
    public function update(RespondToInvitationRequest $request, string $lobbyUlid, string $username): JsonResponse
    {
        $validated = $request->validated();

        $lobby = Lobby::where('ulid', $lobbyUlid)->firstOrFail();
        $currentUser = $request->user();

        // Resolve username to user
        $user = User::where('username', strtolower($username))->firstOrFail();

        // Verify the user is responding for themselves
        if ($currentUser->id !== $user->id) {
            return response()->json(['error' => 'You can only respond for yourself'], 403);
        }

        $lobbyPlayer = LobbyPlayer::where('lobby_id', $lobby->id)
            ->where('user_id', $user->id)
            ->first();

        // If no existing record and lobby is public, allow joining
        if (! $lobbyPlayer && $lobby->is_public && $validated['status'] === 'accepted') {
            $clientId = (int) $request->header('X-Client-Key') ?: 1; // Defaults to Gamer Protocol Web for AI
            
            $lobbyPlayer = LobbyPlayer::create([
                'lobby_id' => $lobby->id,
                'user_id' => $user->id,
                'client_id' => $clientId,
                'status' => LobbyPlayerStatus::ACCEPTED,
            ]);

            // Check if minimum players met
            if ($lobby->hasMinimumPlayers() && ! $lobby->scheduled_at) {
                $this->startGame($lobby);
            }

            return response()->json([
                'message' => 'Successfully joined the lobby',
            ]);
        }

        if (! $lobbyPlayer) {
            return response()->json(['error' => 'You are not invited to this lobby'], 404);
        }

        if ($lobbyPlayer->status !== LobbyPlayerStatus::PENDING) {
            return response()->json(['error' => 'You have already responded to this invitation'], 400);
        }

        // Update status
        if ($validated['status'] === 'accepted') {
            $clientId = (int) $request->header('X-Client-Key') ?: 1; // Defaults to Gamer Protocol Web for AI
            
            $lobbyPlayer->update(['client_id' => $clientId]);
            $lobbyPlayer->accept();

            // Check if minimum players met for immediate (non-scheduled) game
            if ($lobby->hasMinimumPlayers() && ! $lobby->scheduled_at) {
                $this->startGame($lobby);
            }

            return response()->json([
                'message' => 'Invitation accepted',
            ]);
        } else {
            $lobbyPlayer->decline();

            return response()->json([
                'message' => 'Invitation declined',
            ]);
        }
    }

    /**
     * Kick a player from a lobby (Host only)
     */
    public function destroy(Request $request, string $lobbyUlid, string $username): JsonResponse
    {
        $lobby = Lobby::where('ulid', $lobbyUlid)->firstOrFail();
        $currentUser = $request->user();

        if (! $lobby->isHost($currentUser)) {
            return response()->json(['error' => 'Only the host can kick players'], 403);
        }

        // Resolve username to user
        $user = User::where('username', strtolower($username))->firstOrFail();

        if ($user->id === $currentUser->id) {
            return response()->json(['error' => 'Host cannot kick themselves'], 400);
        }

        $lobbyPlayer = LobbyPlayer::where('lobby_id', $lobby->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $lobbyPlayer->delete();

        return response()->json(null, 204);
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
