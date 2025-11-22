<?php

declare(strict_types=1);

namespace App\Matchmaking\Orchestrators;

use App\Matchmaking\Events\ProposalExpired;
use App\Matchmaking\Proposals\ProposalFactory;
use App\Matchmaking\Results\ProposalResult;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Matchmaking\Proposal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates proposal operations (rematch, challenge).
 * Delegates to specific handlers via ProposalFactory.
 */
class ProposalOrchestrator
{
    public function __construct(
        private ProposalFactory $factory
    ) {}

    /**
     * Create a new proposal (rematch or challenge).
     */
    public function createProposal(
        string $type,
        Game $game,
        User $requestingUser,
        ?User $opponentUser = null
    ): ProposalResult {
        try {
            $handler = $this->factory->getHandler($type);

            Log::info('Creating proposal', [
                'type' => $type,
                'game_id' => $game->ulid,
                'requesting_user_id' => $requestingUser->id,
            ]);

            return $handler->create($game, $requestingUser, $opponentUser);
        } catch (\Exception $e) {
            Log::error('Failed to create proposal', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return ProposalResult::failed($e->getMessage());
        }
    }

    /**
     * Accept a proposal.
     */
    public function acceptProposal(
        Proposal $proposal,
        User $acceptingUser,
        bool $isAutoAccept = false
    ): ProposalResult {
        try {
            $handler = $this->factory->getHandler($proposal->type);

            Log::info('Accepting proposal', [
                'proposal_id' => $proposal->ulid,
                'type' => $proposal->type,
                'accepting_user_id' => $acceptingUser->id,
                'is_auto_accept' => $isAutoAccept,
            ]);

            return $handler->accept($proposal, $acceptingUser, $isAutoAccept);
        } catch (\Exception $e) {
            Log::error('Failed to accept proposal', [
                'proposal_id' => $proposal->ulid,
                'error' => $e->getMessage(),
            ]);

            return ProposalResult::failed($e->getMessage());
        }
    }

    /**
     * Decline a proposal.
     */
    public function declineProposal(Proposal $proposal, User $decliningUser): ProposalResult
    {
        try {
            $handler = $this->factory->getHandler($proposal->type);

            Log::info('Declining proposal', [
                'proposal_id' => $proposal->ulid,
                'type' => $proposal->type,
                'declining_user_id' => $decliningUser->id,
            ]);

            return $handler->decline($proposal, $decliningUser);
        } catch (\Exception $e) {
            Log::error('Failed to decline proposal', [
                'proposal_id' => $proposal->ulid,
                'error' => $e->getMessage(),
            ]);

            return ProposalResult::failed($e->getMessage());
        }
    }

    /**
     * Expire old proposals.
     */
    public function expireOldProposals(): int
    {
        $expired = Proposal::where('status', 'pending')
            ->where('expires_at', '<=', Carbon::now())
            ->get();

        foreach ($expired as $proposal) {
            $proposal->update([
                'status' => 'expired',
                'responded_at' => now(),
            ]);
            event(new ProposalExpired($proposal));
        }

        Log::info('Expired old proposals', ['count' => $expired->count()]);

        return $expired->count();
    }
}
