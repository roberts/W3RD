<?php

declare(strict_types=1);

namespace App\Matchmaking\Orchestrators;

use App\Enums\GameTitle;
use App\Enums\PlayerActivityState;
use App\Matchmaking\Queue\QueueManager;
use App\Matchmaking\Queue\SlotManager;
use App\Matchmaking\Results\QueueResult;
use App\Matchmaking\Shared\PlayerAvailabilityChecker;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the complete queue matchmaking workflow.
 * Coordinates queue operations, slot creation, and availability checks.
 */
class QueueOrchestrator
{
    public function __construct(
        private PlayerAvailabilityChecker $availabilityChecker,
        private QueueManager $queueManager,
        private SlotManager $slotManager
    ) {}

    /**
     * Join the matchmaking queue.
     *
     * Workflow:
     * 1. Attempt to join the queue (checks cooldown)
     * 2. Create queue slot
     * 3. Update player state to IN_QUEUE
     * 4. Return success result with slot
     *
     * @param  array<string, mixed>  $preferences
     */
    public function joinQueue(
        User $user,
        GameTitle $gameTitle,
        string $gameMode,
        int $modeId,
        int $clientId,
        array $preferences = [],
        ?int $skillRating = null
    ): QueueResult {
        // Step 1: Attempt to join queue (handles cooldown check)
        $queueResult = $this->queueManager->joinQueue($user, $gameTitle, $gameMode, $clientId);

        if (! $queueResult->success) {
            Log::info('Queue join failed: Cooldown active', [
                'user_id' => $user->id,
                'cooldown_remaining' => $queueResult->cooldownRemaining,
            ]);

            return QueueResult::cooldownActive(
                $queueResult->cooldownRemaining,
                $queueResult->errorMessage
            );
        }

        // Step 2: Create queue slot
        $slot = $this->slotManager->createSlot(
            $user,
            $gameTitle,
            $gameMode,
            $modeId,
            $clientId,
            $preferences,
            $skillRating
        );

        // Step 3: Update player state (note: JoinQueueAction already sets this, but we do it again for clarity)
        $this->availabilityChecker->setState($user->id, PlayerActivityState::IN_QUEUE);

        Log::info('Player joined matchmaking queue', [
            'user_id' => $user->id,
            'slot_id' => $slot->id,
            'game_title' => $gameTitle->value,
            'game_mode' => $gameMode,
        ]);

        return QueueResult::success($slot);
    }

    /**
     * Process a user leaving the matchmaking queue.
     *
     * Workflow:
     * 1. Remove user from the queue
     * 2. Cancel their queue slot
     * 3. Return success result
     */
    public function cancelQueue(User $user): QueueResult
    {
        try {
            // Step 1: Leave the queue
            $this->queueManager->leaveQueue($user);

            // Step 2: Cancel the slot
            $slot = $this->slotManager->cancelSlot($user);

            // Step 3: Clear player state
            $this->availabilityChecker->setState($user->id, PlayerActivityState::IDLE);

            Log::info('Player left matchmaking queue', [
                'user_id' => $user->id,
            ]);

            return QueueResult::success($slot);
        } catch (\Exception $e) {
            Log::error('Failed to leave matchmaking queue', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return QueueResult::failed(
                'Failed to leave matchmaking queue.',
                ['error' => $e->getMessage()]
            );
        }
    }
}
