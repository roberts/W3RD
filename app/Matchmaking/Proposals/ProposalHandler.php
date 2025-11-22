<?php

declare(strict_types=1);

namespace App\Matchmaking\Proposals;

use App\Matchmaking\Results\ProposalResult;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Proposal;

/**
 * Interface for handling different types of proposals (rematch, challenge).
 */
interface ProposalHandler
{
    /**
     * Create a new proposal.
     */
    public function create(Game $game, User $requestingUser, ?User $opponentUser = null): ProposalResult;

    /**
     * Accept a proposal and create the resulting game.
     */
    public function accept(Proposal $proposal, User $acceptingUser, bool $isAutoAccept = false): ProposalResult;

    /**
     * Decline a proposal.
     */
    public function decline(Proposal $proposal, User $decliningUser): ProposalResult;

    /**
     * Check if this handler supports the given proposal type.
     */
    public function supports(string $type): bool;
}
