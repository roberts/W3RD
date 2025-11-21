<?php

namespace App\Services;

use App\Enums\GameStatus;
use App\Events\RematchAccepted;
use App\Events\RematchDeclined;
use App\Events\RematchExpired;
use App\Events\RematchRequested;
use App\Exceptions\RematchNotAvailableException;
use App\Jobs\AgentAutoAcceptRematch;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Player;
use App\Models\Game\Proposal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RematchService
{
    /**
     * Create a rematch request.
     */
    public function createRematchRequest(Game $game, User $requestingUser): Proposal
    {
        $status = $this->resolveGameStatus($game);

        // Validate game is completed
        if ($status !== GameStatus::COMPLETED) {
            throw new RematchNotAvailableException('Can only request rematch for completed games.');
        }

        // Validate requesting user was a player
        $player = $game->players()->where('user_id', $requestingUser->id)->first();
        if (! $player) {
            throw new RematchNotAvailableException('User was not a player in this game.');
        }

        // Get opponent
        /** @var Player|null $opponent */
        $opponent = $game->players()
            ->where('user_id', '!=', $requestingUser->id)
            ->first();

        if (! $opponent) {
            throw new RematchNotAvailableException('Could not find opponent for rematch.');
        }

        // Check if opponent is available for rematch
        $activityService = app(PlayerActivityService::class);
        $opponentState = $activityService->getState($opponent->user_id);

        if (! $opponentState->isAvailableForRematch()) {
            throw new RematchNotAvailableException(
                "Opponent is currently {$opponentState->value}. Cannot request rematch."
            );
        }

        // If opponent is an agent, check if cooldown period has expired
        $opponentUser = User::find($opponent->user_id);
        if ($opponentUser && $opponentUser->isAgent()) {
            $cooldownKey = "agent:{$opponentUser->id}:cooldown";

            if (! Redis::exists($cooldownKey)) {
                // Cooldown expired - agent is no longer available for instant rematch
                throw new RematchNotAvailableException('Opponent is no longer available for rematch.');
            }
        }

        // Check for existing pending request
        $existing = Proposal::where('original_game_id', $game->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            throw new RematchNotAvailableException('A rematch request already exists for this game.');
        }

        $expirationMinutes = config('protocol.floor.proposals.expiration_minutes', 5);

        $rematchRequest = Proposal::create([
            'original_game_id' => $game->id,
            'requesting_user_id' => $requestingUser->id,
            'opponent_user_id' => $opponent->user_id,
            'title_slug' => $game->title_slug->value,
            'mode_id' => $game->mode_id,
            'type' => 'rematch',
            'game_settings' => $game->game_settings,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addMinutes($expirationMinutes),
        ]);

        // If opponent is agent within cooldown window, schedule auto-accept
        if ($opponentUser && $opponentUser->isAgent()) {
            $cooldownKey = "agent:{$opponentUser->id}:cooldown";
            $cooldownData = Redis::hgetall($cooldownKey);

            if (! empty($cooldownData) &&
                (int) $cooldownData['human_user_id'] === $requestingUser->id) {

                // Schedule delayed auto-accept (1-7 seconds random)
                $delay = rand(1, 7);

                dispatch(new AgentAutoAcceptRematch($rematchRequest->ulid, $opponentUser->id))
                    ->delay(now()->addSeconds($delay));

                Log::info('Scheduled agent auto-accept', [
                    'rematch_request_id' => $rematchRequest->ulid,
                    'agent_id' => $opponentUser->id,
                    'delay_seconds' => $delay,
                ]);
            }
        }

        event(new RematchRequested($rematchRequest));

        return $rematchRequest;
    }

    /**
     * Accept a rematch request and create a new game.
     */
    public function acceptRematchRequest(Proposal $rematchRequest, User $acceptingUser, bool $isAutoAccept = false): Game
    {
        // Validate user is the opponent (skip for auto-accepts)
        if (! $isAutoAccept && $rematchRequest->opponent_user_id !== $acceptingUser->id) {
            throw new RematchNotAvailableException('Only the opponent can accept this rematch request.');
        }

        // Validate request is still pending
        if ($rematchRequest->status !== 'pending') {
            throw new RematchNotAvailableException('This rematch request is no longer pending.');
        }

        // Validate not expired
        if ($rematchRequest->expires_at->isPast()) {
            $rematchRequest->update(['status' => 'expired']);
            throw new RematchNotAvailableException('This rematch request has expired.');
        }

        return DB::transaction(function () use ($rematchRequest) {
            /** @var Game $originalGame */
            $originalGame = $rematchRequest->originalGame;

            // Get the mode to determine how to initialize game state
            $mode = $originalGame->mode;
            $players = $originalGame->players;

            // Swap player positions for fairness
            $player1 = $players->firstWhere('position_id', 1);
            $player2 = $players->firstWhere('position_id', 2);

            // Initialize game state based on game title
            $gameState = [];
            if ($originalGame->title_slug->value === 'connect-four') {
                $gameState = [
                    'board' => array_fill(0, 6, array_fill(0, 7, null)),
                    'current_player_ulid' => $player2->ulid, // Swapped - was player2, now position 1
                    'columns' => 7,
                    'rows' => 6,
                    'connect_count' => 4,
                    'players' => [
                        $player2->ulid => ['ulid' => $player2->ulid, 'position' => 1, 'color' => 'red'],
                        $player1->ulid => ['ulid' => $player1->ulid, 'position' => 2, 'color' => 'yellow'],
                    ],
                    'phase' => 'active',
                    'status' => 'pending',
                ];
            }

            // Create new game with same settings
            $newGame = Game::create([
                'title_slug' => $originalGame->title_slug,
                'mode_id' => $originalGame->mode_id,
                'creator_id' => $originalGame->creator_id,
                'status' => GameStatus::PENDING,
                'game_state' => $gameState,
            ]);

            // Copy players to new game (swap positions for fairness)
            /** @var Player $player */
            foreach ($originalGame->players as $player) {
                $newGame->players()->create([
                    'user_id' => $player->user_id,
                    'client_id' => $player->client_id, // Maintains client from original game (1 = Gamer Protocol Web for AI)
                    'color' => $player->color,
                    'position_id' => $player->position_id === 1 ? 2 : 1, // Swap positions
                ]);
            }

            // Update rematch request
            $rematchRequest->update([
                'status' => 'accepted',
                'game_id' => $newGame->id,
                'responded_at' => now(),
            ]);

            event(new RematchAccepted($rematchRequest, $newGame));

            return $newGame;
        });
    }

    /**
     * Decline a rematch request.
     */
    public function declineRematchRequest(Proposal $rematchRequest, User $decliningUser): Proposal
    {
        // Validate user is the opponent by comparing user ID directly
        if ($rematchRequest->opponent_user_id !== $decliningUser->id) {
            throw new AccessDeniedHttpException('Only the opponent can decline this rematch request.');
        }

        // Validate request is still pending
        if ($rematchRequest->status !== 'pending') {
            throw new RematchNotAvailableException('This rematch request is no longer pending.');
        }

        $rematchRequest->update([
            'status' => 'declined',
            'responded_at' => now(),
        ]);

        event(new RematchDeclined($rematchRequest));

        return $rematchRequest;
    }

    /**
     * Expire old rematch requests.
     */
    public function expireOldRequests(): int
    {
        $expired = Proposal::where('status', 'pending')
            ->where('expires_at', '<=', Carbon::now())
            ->get();

        foreach ($expired as $request) {
            $request->update([
                'status' => 'expired',
                'responded_at' => now(),
            ]);
            event(new RematchExpired($request));
        }

        return $expired->count();
    }

    private function resolveGameStatus(Game $game): GameStatus
    {
        return $game->status;
    }
}
