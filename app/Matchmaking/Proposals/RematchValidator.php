<?php

declare(strict_types=1);

namespace App\Matchmaking\Proposals;

use App\Enums\GameStatus;
use App\Exceptions\RematchNotAvailableException;
use App\GameEngine\Player\PlayerActivityManager;
use App\Matchmaking\Enums\ProposalStatus;
use App\Models\Auth\User;
use App\Models\Games\Game;
use App\Models\Games\Player;
use App\Models\Matchmaking\Proposal;
use Illuminate\Support\Facades\Redis;

/**
 * Validates rematch request conditions.
 */
class RematchValidator
{
    public function __construct(
        private PlayerActivityManager $playerActivityManager
    ) {}

    /**
     * Validate that a rematch can be requested for this game.
     *
     * @throws RematchNotAvailableException
     */
    public function validateRematchRequest(Game $game, User $requestingUser): Player
    {
        // Validate game is completed
        if ($game->status !== GameStatus::COMPLETED) {
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
        $opponentState = $this->playerActivityManager->getState($opponent->user_id);

        if ($opponentState && ! $opponentState->isAvailableForRematch()) {
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

        return $opponent;
    }

    /**
     * Validate that a rematch can be accepted.
     *
     * @throws RematchNotAvailableException
     */
    public function validateAcceptance(Proposal $proposal, User $acceptingUser, bool $isAutoAccept = false): void
    {
        // Validate user is the opponent (skip for auto-accepts)
        if (! $isAutoAccept && $proposal->opponent_user_id !== $acceptingUser->id) {
            throw new RematchNotAvailableException('Only the opponent can accept this rematch request.');
        }

        // Validate request is still pending
        if ($proposal->status !== ProposalStatus::PENDING) {
            throw new RematchNotAvailableException('This rematch request is no longer pending.');
        }

        // Validate not expired
        if ($proposal->expires_at->isPast()) {
            $proposal->update(['status' => ProposalStatus::EXPIRED]);
            throw new RematchNotAvailableException('This rematch request has expired.');
        }
    }
}
