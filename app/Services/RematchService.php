<?php

namespace App\Services;

use App\Enums\GameStatus;
use App\Events\RematchAccepted;
use App\Events\RematchDeclined;
use App\Events\RematchExpired;
use App\Events\RematchRequested;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Player;
use App\Models\Game\RematchRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RematchService
{
    /**
     * Create a rematch request.
     */
    public function createRematchRequest(Game $game, User $requestingUser): RematchRequest
    {
        // Validate game is completed
        if ($game->status !== GameStatus::COMPLETED) {
            throw new \InvalidArgumentException('Can only request rematch for completed games.');
        }

        // Validate requesting user was a player
        $player = $game->players()->where('user_id', $requestingUser->id)->first();
        if (! $player) {
            throw new \InvalidArgumentException('User was not a player in this game.');
        }

        // Get opponent
        /** @var Player|null $opponent */
        $opponent = $game->players()
            ->where('user_id', '!=', $requestingUser->id)
            ->first();

        if (! $opponent) {
            throw new \InvalidArgumentException('Could not find opponent for rematch.');
        }

        // Check for existing pending request
        $existing = RematchRequest::where('original_game_id', $game->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            throw new \InvalidArgumentException('A rematch request already exists for this game.');
        }

        $expirationMinutes = config('protocol.rematch.expiration_minutes', 5);

        $rematchRequest = RematchRequest::create([
            'original_game_id' => $game->id,
            'requesting_user_id' => $requestingUser->id,
            'opponent_user_id' => $opponent->user_id,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addMinutes($expirationMinutes),
        ]);

        event(new RematchRequested($rematchRequest));

        return $rematchRequest;
    }

    /**
     * Accept a rematch request and create a new game.
     */
    public function acceptRematchRequest(RematchRequest $rematchRequest, User $acceptingUser): Game
    {
        // Validate user is the opponent
        if ($rematchRequest->opponent_user_id !== $acceptingUser->id) {
            throw new \InvalidArgumentException('Only the opponent can accept this rematch request.');
        }

        // Validate request is still pending
        if ($rematchRequest->status !== 'pending') {
            throw new \InvalidArgumentException('This rematch request is no longer pending.');
        }

        // Validate not expired
        if ($rematchRequest->expires_at->isPast()) {
            $rematchRequest->update(['status' => 'expired']);
            throw new \InvalidArgumentException('This rematch request has expired.');
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
            if ($originalGame->title_slug->value === 'validate-four') {
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
            /** @var \App\Models\Game\Player $player */
            foreach ($originalGame->players as $player) {
                $newGame->players()->create([
                    'user_id' => $player->user_id,
                    'name' => $player->name,
                    'color' => $player->color,
                    'position_id' => $player->position_id === 1 ? 2 : 1, // Swap positions
                ]);
            }

            // Update rematch request
            $rematchRequest->update([
                'status' => 'accepted',
                'new_game_id' => $newGame->id,
            ]);

            event(new RematchAccepted($rematchRequest, $newGame));

            return $newGame;
        });
    }

    /**
     * Decline a rematch request.
     */
    public function declineRematchRequest(RematchRequest $rematchRequest, User $decliningUser): RematchRequest
    {
        // Validate user is the opponent
        if ($rematchRequest->opponent_user_id !== $decliningUser->id) {
            throw new AccessDeniedHttpException('Only the opponent can decline this rematch request.');
        }

        // Validate request is still pending
        if ($rematchRequest->status !== 'pending') {
            throw new \InvalidArgumentException('This rematch request is no longer pending.');
        }

        $rematchRequest->update(['status' => 'declined']);

        event(new RematchDeclined($rematchRequest));

        return $rematchRequest;
    }

    /**
     * Expire old rematch requests.
     */
    public function expireOldRequests(): int
    {
        $expired = RematchRequest::where('status', 'pending')
            ->where('expires_at', '<=', Carbon::now())
            ->get();

        foreach ($expired as $request) {
            $request->update(['status' => 'expired']);
            event(new RematchExpired($request));
        }

        return $expired->count();
    }
}
