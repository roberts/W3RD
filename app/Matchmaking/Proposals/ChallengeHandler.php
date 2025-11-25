<?php

declare(strict_types=1);

namespace App\Matchmaking\Proposals;

use App\Matchmaking\Results\ProposalResult;
use App\Models\Auth\User;
use App\Models\Games\Game;
use App\Models\Matchmaking\Proposal;

/**
 * Handles direct challenge proposals between players.
 * Future implementation for direct challenges.
 */
class ChallengeHandler implements ProposalHandler
{
    public function supports(string $type): bool
    {
        return $type === 'challenge';
    }

    public function create(Game $game, User $requestingUser, ?User $opponentUser = null): ProposalResult
    {
        // Future implementation for direct challenges
        return ProposalResult::failed('Challenge proposals are not yet implemented.');
    }

    public function accept(Proposal $proposal, User $acceptingUser, bool $isAutoAccept = false): ProposalResult
    {
        return ProposalResult::failed('Challenge proposals are not yet implemented.');
    }

    public function decline(Proposal $proposal, User $decliningUser): ProposalResult
    {
        return ProposalResult::failed('Challenge proposals are not yet implemented.');
    }
}
