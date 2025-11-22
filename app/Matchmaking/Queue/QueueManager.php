<?php

declare(strict_types=1);

namespace App\Matchmaking\Queue;

use App\Matchmaking\Queue\Actions\JoinQueueAction;
use App\Matchmaking\Queue\Actions\LeaveQueueAction;
use App\DataTransferObjects\Matchmaking\QueueJoinResult;
use App\Enums\GameTitle;
use App\Models\Auth\User;

/**
 * Manages queue operations (join, leave).
 */
class QueueManager
{
    public function __construct(
        private JoinQueueAction $joinQueue,
        private LeaveQueueAction $leaveQueue
    ) {}

    /**
     * Add a user to the matchmaking queue.
     */
    public function joinQueue(
        User $user,
        GameTitle $gameTitle,
        string $gameMode,
        int $clientId
    ): QueueJoinResult {
        return $this->joinQueue->execute($user, $gameTitle, $gameMode, $clientId);
    }

    /**
     * Remove a user from the matchmaking queue.
     */
    public function leaveQueue(User $user): void
    {
        $this->leaveQueue->execute($user);
    }
}
