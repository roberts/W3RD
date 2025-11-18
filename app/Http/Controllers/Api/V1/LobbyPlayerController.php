<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LobbyPlayerStatus;
use App\Enums\LobbyStatus;
use App\Events\LobbyInvitation;
use App\Http\Requests\Lobby\InvitePlayerRequest;
use App\Http\Requests\Lobby\RespondToInvitationRequest;
use App\Http\Traits\ApiResponses;
use App\Models\Auth\User;
use App\Models\Game\Lobby;
use App\Models\Game\LobbyPlayer;
use App\Services\GameCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LobbyPlayerController extends Controller
{
    use ApiResponses;
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
            return $this->forbiddenResponse('Only the host can invite players');
        }

        if ($lobby->status !== LobbyStatus::PENDING) {
            return $this->errorResponse('Cannot invite players to a non-pending lobby');
        }

        // Resolve username to user
        $invitee = User::where('username', $validated['username'])->firstOrFail();

        // Check if player is already in lobby
        $existing = LobbyPlayer::where('lobby_id', $lobby->id)
            ->where('user_id', $invitee->id)
            ->first();

        if ($existing) {
            return $this->errorResponse('Player is already in this lobby');
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

        $lobby = Lobby::where('ulid', $lobbyUlid)->firstOrFail();
        $currentUser = $request->user();

        // Resolve username to user
        $user = User::where('username', strtolower($username))->firstOrFail();

        // Verify the user is responding for themselves
        if ($currentUser->id !== $user->id) {
            return $this->forbiddenResponse('You can only respond for yourself');
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

            // Check if we can start the game (exact player count for games that require it)
            if ($lobby->canStartGame() && ! $lobby->scheduled_at) {
                $this->startGame($lobby);
            }

            return $this->successResponse(null, 'Successfully joined the lobby');
        }

        if (! $lobbyPlayer) {
            return $this->notFoundResponse('You are not invited to this lobby');
        }

        if ($lobbyPlayer->status !== LobbyPlayerStatus::PENDING) {
            return $this->errorResponse('You have already responded to this invitation');
        }

        // Update status
        if ($validated['status'] === 'accepted') {
            $clientId = (int) $request->header('X-Client-Key') ?: 1; // Defaults to Gamer Protocol Web for AI

            $lobbyPlayer->update(['client_id' => $clientId]);
            $lobbyPlayer->accept();

            // Check if we can start the game (exact player count for games that require it)
            if ($lobby->canStartGame() && ! $lobby->scheduled_at) {
                $this->startGame($lobby);
            }

            return $this->successResponse(null, 'Invitation accepted');
        } else {
            $lobbyPlayer->decline();

            return $this->successResponse(null, 'Invitation declined');
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
            return $this->forbiddenResponse('Only the host can kick players');
        }

        // Resolve username to user
        $user = User::where('username', strtolower($username))->firstOrFail();

        if ($user->id === $currentUser->id) {
            return $this->errorResponse('Host cannot kick themselves');
        }

        $lobbyPlayer = LobbyPlayer::where('lobby_id', $lobby->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $lobbyPlayer->delete();

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
