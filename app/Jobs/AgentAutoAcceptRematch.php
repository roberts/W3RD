<?php

namespace App\Jobs;

use App\Enums\PlayerActivityState;
use App\GameEngine\Player\PlayerActivityManager;
use App\Matchmaking\Enums\ProposalStatus;
use App\Matchmaking\Events\ProposalCancelled;
use App\Matchmaking\Orchestrators\ProposalOrchestrator;
use App\Models\Auth\User;
use App\Models\Matchmaking\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AgentAutoAcceptRematch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $proposalId,
        public int $agentUserId,
        public PlayerActivityManager $playerActivityManager
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $proposal = Proposal::where('ulid', $this->proposalId)->first();

        if (! $proposal) {
            Log::warning('Rematch request not found for auto-accept', [
                'proposal_id' => $this->proposalId,
            ]);

            return;
        }

        // Check if still pending
        if ($proposal->status !== ProposalStatus::PENDING) {
            Log::info('Rematch request no longer pending', [
                'proposal_id' => $this->proposalId,
                'status' => $proposal->status->value,
            ]);

            return;
        }

        // Check if agent is still available
        $agentState = $this->playerActivityManager->getState($this->agentUserId);

        if ($agentState !== PlayerActivityState::IDLE) {
            Log::info('Agent no longer available for auto-accept', [
                'proposal_id' => $this->proposalId,
                'agent_id' => $this->agentUserId,
                'agent_state' => $agentState ? $agentState->value : 'null',
            ]);

            // Cancel the rematch
            $proposal->update(['status' => 'cancelled']);
            event(new ProposalCancelled($proposal, 'opponent_unavailable'));

            return;
        }

        // Check if requesting user is still available
        $requesterState = $this->playerActivityManager->getState($proposal->requesting_user_id);
        if ($requesterState !== PlayerActivityState::IDLE) {
            Log::info('Requesting user no longer available', [
                'proposal_id' => $this->proposalId,
                'requester_id' => $proposal->requesting_user_id,
                'requester_state' => $requesterState ? $requesterState->value : 'null',
            ]);

            $proposal->update(['status' => 'cancelled']);
            event(new ProposalCancelled($proposal, 'requester_unavailable'));

            return;
        }

        // All checks passed - auto-accept!
        $agentUser = User::find($this->agentUserId);

        if (! $agentUser) {
            Log::warning('Agent user not found for auto-accept', [
                'proposal_id' => $this->proposalId,
                'agent_id' => $this->agentUserId,
            ]);

            $proposal->update(['status' => 'cancelled']);
            event(new ProposalCancelled($proposal, 'opponent_unavailable'));

            return;
        }

        $orchestrator = app(ProposalOrchestrator::class);

        try {
            $result = $orchestrator->acceptProposal($proposal, $agentUser, isAutoAccept: true);

            if (! $result->success) {
                Log::warning('Agent auto-accept failed', [
                    'proposal_id' => $proposal->ulid,
                    'agent_id' => $this->agentUserId,
                    'error' => $result->errorMessage,
                ]);

                return;
            }

            $newGame = $result->game;

            Log::info('Agent auto-accepted rematch', [
                'proposal_id' => $this->proposalId,
                'agent_id' => $this->agentUserId,
                'game_id' => $newGame->id,
            ]);

            // Clear agent cooldown
            Redis::del("agent:{$this->agentUserId}:cooldown");

        } catch (\Exception $e) {
            Log::error('Agent auto-accept failed', [
                'proposal_id' => $this->proposalId,
                'agent_id' => $this->agentUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
