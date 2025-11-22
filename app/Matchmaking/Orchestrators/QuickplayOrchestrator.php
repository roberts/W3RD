<?php

declare(strict_types=1);

namespace App\Matchmaking\Orchestrators;

use App\Enums\GameTitle;
use App\Enums\PlayerActivityState;
use App\Matchmaking\Quickplay\QueueManager;
use App\Matchmaking\Quickplay\SignalManager;
use App\Matchmaking\Results\QuickplayResult;
use App\Matchmaking\Shared\PlayerAvailabilityChecker;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the complete quickplay matchmaking workflow.
 * Coordinates queue operations, signal creation, and availability checks.
 */
class QuickplayOrchestrator
{
    public function __construct(
        private PlayerAvailabilityChecker $availabilityChecker,
        private QueueManager $queueManager,
        private SignalManager $signalManager
    ) {}

    /**
     * Process a user joining the quickplay queue.
     * 
     * Workflow:
     * 1. Attempt to join the queue (checks cooldown)
     * 2. Create matchmaking signal
     * 3. Update player state to IN_QUEUE
     * 4. Return success result with signal
     */
    public function joinQueue(
        User $user,
        GameTitle $gameTitle,
        string $gameMode,
        int $clientId,
        array $preferences = [],
        ?int $skillRating = null
    ): QuickplayResult {
        // Step 1: Attempt to join queue (handles cooldown check)
        $queueResult = $this->queueManager->joinQueue($user, $gameTitle, $gameMode, $clientId);
        
        if (! $queueResult->success) {
            Log::info('Quickplay join failed: Cooldown active', [
                'user_id' => $user->id,
                'cooldown_remaining' => $queueResult->cooldownRemaining,
            ]);
            
            return QuickplayResult::cooldownActive(
                $queueResult->cooldownRemaining,
                $queueResult->errorMessage
            );
        }

        // Step 2: Create matchmaking signal
        $signal = $this->signalManager->createSignal(
            $user,
            $gameTitle,
            $gameMode,
            $clientId,
            $preferences,
            $skillRating
        );

        // Step 3: Update player state (note: JoinQuickplayQueueAction already sets this, but we do it again for clarity)
        $this->availabilityChecker->setState($user->id, PlayerActivityState::IN_QUEUE);
        
        Log::info('Player joined quickplay queue', [
            'user_id' => $user->id,
            'signal_id' => $signal->id,
            'game_title' => $gameTitle->value,
            'game_mode' => $gameMode,
        ]);
        
        return QuickplayResult::success($signal);
    }

    /**
     * Process a user leaving the quickplay queue.
     * 
     * Workflow:
     * 1. Remove user from the queue
     * 2. Cancel their matchmaking signal
     * 3. Return success result
     */
    public function cancelQueue(User $user): QuickplayResult
    {
        try {
            // Step 1: Leave the queue
            $this->queueManager->leaveQueue($user);
            
            // Step 2: Cancel the signal
            $signal = $this->signalManager->cancelSignal($user);
            
            // Step 3: Clear player state
            $this->availabilityChecker->setState($user->id, PlayerActivityState::IDLE);
            
            Log::info('Player left quickplay queue', [
                'user_id' => $user->id,
            ]);
            
            return QuickplayResult::success($signal);
        } catch (\Exception $e) {
            Log::error('Failed to leave quickplay queue', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return QuickplayResult::failed(
                'Failed to leave matchmaking queue.',
                ['error' => $e->getMessage()]
            );
        }
    }
}
