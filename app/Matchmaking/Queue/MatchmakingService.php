<?php

declare(strict_types=1);

namespace App\Matchmaking\Queue;

use App\Enums\GameTitle;
use Illuminate\Support\Facades\Redis;

/**
 * Core matchmaking service that processes the queue and matches players.
 */
class MatchmakingService
{
    private const AI_FALLBACK_THRESHOLD = 20; // seconds

    public function __construct(
        private OpponentFinder $opponentFinder,
        private AgentMatcher $agentMatcher,
        private MatchConfirmationHandler $matchConfirmationHandler
    ) {}

    /**
     * Process the matchmaking queue for a specific game and mode.
     */
    public function processQueue(GameTitle $gameTitle, string $mode): void
    {
        $queueKey = "queue:{$gameTitle->value}:{$mode}";

        // Get all players in queue
        $players = Redis::zrange($queueKey, 0, -1, ['WITHSCORES' => true]);

        if (empty($players)) {
            return;
        }

        // Process each player
        $playersArray = $this->parseQueuePlayers($players);

        foreach ($playersArray as $player) {
            $userId = $player['user_id'];
            $waitTime = $this->opponentFinder->getWaitTime($userId);

            // Check for AI fallback
            if ($waitTime >= self::AI_FALLBACK_THRESHOLD) {
                $this->agentMatcher->matchWithAgent($userId, $gameTitle, $mode, $queueKey);
                continue;
            }

            // Try to find human opponent
            $opponent = $this->opponentFinder->findOpponent($player, $playersArray, $queueKey);

            if ($opponent) {
                $this->matchConfirmationHandler->createMatchConfirmation(
                    $userId,
                    $opponent['user_id'],
                    $gameTitle,
                    $mode,
                    $queueKey
                );
            }
        }
    }

    /**
     * Parse Redis queue data into array of players.
     *
     * @param  array<mixed>  $players
     * @return array<array{user_id: int, skill: int}>
     */
    private function parseQueuePlayers(array $players): array
    {
        $playersArray = [];

        for ($i = 0; $i < count($players); $i += 2) {
            $playersArray[] = [
                'user_id' => (int) $players[$i],
                'skill' => (int) $players[$i + 1],
            ];
        }

        return $playersArray;
    }

    /**
     * Get the AI fallback threshold in seconds.
     */
    public function getAiFallbackThreshold(): int
    {
        return self::AI_FALLBACK_THRESHOLD;
    }
}
