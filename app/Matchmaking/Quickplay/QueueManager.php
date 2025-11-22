<?php

declare(strict_types=1);

namespace App\Matchmaking\Quickplay;

use App\Actions\Quickplay\JoinQuickplayQueueAction;
use App\Actions\Quickplay\LeaveQuickplayQueueAction;
use App\DataTransferObjects\Quickplay\QueueJoinResult;
use App\Enums\GameTitle;
use App\Models\Auth\User;

/**
 * Manages quickplay queue operations (join, leave).
 */
class QueueManager
{
    public function __construct(
        private JoinQuickplayQueueAction $joinQueue,
        private LeaveQuickplayQueueAction $leaveQueue
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
