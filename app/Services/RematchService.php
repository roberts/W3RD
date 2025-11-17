<?php

namespace App\Services;

use App\Events\RematchAccepted;
use App\Events\RematchDeclined;
use App\Events\RematchExpired;
use App\Events\RematchRequested;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\RematchRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RematchService
{
    /**
     * Create a rematch request.
     */
    public function createRematchRequest(Game $game, User $requestingUser): RematchRequest
    {
        // Validate game is completed
        if ($game->status !== 'completed') {
            throw new \InvalidArgumentException('Can only request rematch for completed games.');
        }

        // Validate requesting user was a player
        $player = $game->players()->where('user_id', $requestingUser->id)->first();
        if (!$player) {
            throw new \InvalidArgumentException('User was not a player in this game.');
        }

        // Get opponent
        $opponent = $game->players()
            ->where('user_id', '!=', $requestingUser->id)
            ->first();

        if (!$opponent) {
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
            $originalGame = $rematchRequest->originalGame;

            // Create new game with same settings
            $newGame = Game::create([
                'mode_id' => $originalGame->mode_id,
                'status' => 'pending',
                'current_turn_user_id' => null,
                'board_state' => $originalGame->mode->initial_board_state ?? [],
            ]);

            // Copy players to new game (swap positions for fairness)
            foreach ($originalGame->players as $player) {
                $newGame->players()->create([
                    'user_id' => $player->user_id,
                    'position' => $player->position === 1 ? 2 : 1, // Swap positions
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
            throw new \InvalidArgumentException('Only the opponent can decline this rematch request.');
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
