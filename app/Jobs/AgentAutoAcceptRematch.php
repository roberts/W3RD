<?php

namespace App\Jobs;

use App\Enums\PlayerActivityState;
use App\Events\RematchCancelled;
use App\Models\Auth\User;
use App\Models\Game\RematchRequest;
use App\Services\PlayerActivityService;
use App\Services\RematchService;
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
        public string $rematchRequestId,
        public int $agentUserId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $rematchRequest = RematchRequest::where('ulid', $this->rematchRequestId)->first();

        if (! $rematchRequest) {
            Log::warning('Rematch request not found for auto-accept', [
                'rematch_request_id' => $this->rematchRequestId,
            ]);

            return;
        }

        // Check if still pending
        if ($rematchRequest->status !== 'pending') {
            Log::info('Rematch request no longer pending', [
                'rematch_request_id' => $this->rematchRequestId,
                'status' => $rematchRequest->status,
            ]);

            return;
        }

        $activityService = app(PlayerActivityService::class);

        // Check if agent is still available
        $agentState = $activityService->getState($this->agentUserId);

        if ($agentState !== PlayerActivityState::IDLE) {
            Log::info('Agent no longer available for auto-accept', [
                'rematch_request_id' => $this->rematchRequestId,
                'agent_id' => $this->agentUserId,
                'agent_state' => $agentState->value,
            ]);

            // Cancel the rematch
            $rematchRequest->update(['status' => 'cancelled']);
            event(new RematchCancelled($rematchRequest, 'opponent_unavailable'));

            return;
        }

        // Check if requesting user is still available
        $requesterState = $activityService->getState($rematchRequest->requesting_user_id);
        if ($requesterState !== PlayerActivityState::IDLE) {
            Log::info('Requesting user no longer available', [
                'rematch_request_id' => $this->rematchRequestId,
                'requester_id' => $rematchRequest->requesting_user_id,
                'requester_state' => $requesterState->value,
            ]);

            $rematchRequest->update(['status' => 'cancelled']);
            event(new RematchCancelled($rematchRequest, 'requester_unavailable'));

            return;
        }

        // All checks passed - auto-accept!
        $agentUser = User::find($this->agentUserId);

        if (! $agentUser) {
            Log::warning('Agent user not found for auto-accept', [
                'rematch_request_id' => $this->rematchRequestId,
                'agent_id' => $this->agentUserId,
            ]);

            $rematchRequest->update(['status' => 'cancelled']);
            event(new RematchCancelled($rematchRequest, 'opponent_unavailable'));

            return;
        }

        $rematchService = app(RematchService::class);

        try {
            $newGame = $rematchService->acceptRematchRequest($rematchRequest, $agentUser, isAutoAccept: true);

            Log::info('Agent auto-accepted rematch', [
                'rematch_request_id' => $this->rematchRequestId,
                'agent_id' => $this->agentUserId,
                'new_game_id' => $newGame->id,
            ]);

            // Clear agent cooldown
            Redis::del("agent:{$this->agentUserId}:cooldown");

        } catch (\Exception $e) {
            Log::error('Agent auto-accept failed', [
                'rematch_request_id' => $this->rematchRequestId,
                'agent_id' => $this->agentUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
