<?php

declare(strict_types=1);

namespace App\Matchmaking\Lobby;

use App\Matchmaking\Enums\LobbyPlayerSource;
use App\Matchmaking\Enums\LobbyPlayerStatus;
use App\Matchmaking\Enums\QueueSlotStatus;
use App\Matchmaking\Queue\RecentOpponentTracker;
use App\Models\Matchmaking\Lobby;
use App\Models\Matchmaking\LobbyPlayer;
use App\Models\Matchmaking\QueueSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Fills public lobbies with players from the queue.
 */
class LobbyQueueFiller
{
    private const RECENT_OPPONENT_LIMIT = 3;

    private const READY_CHECK_THRESHOLD_SECONDS = 60;

    public function __construct(
        private LobbyGameStarter $lobbyGameStarter,
        private RecentOpponentTracker $recentOpponentTracker
    ) {}

    /**
     * Attempt to fill a lobby with queue players.
     *
     * Returns true if lobby was successfully filled, false otherwise.
     */
    public function tryFillLobby(Lobby $lobby): bool
    {
        // Determine how many players needed
        $requiredPlayers = $lobby->title_slug->requiresExactPlayerCount()
            ? $lobby->title_slug->maxPlayers()
            : $lobby->min_players;

        $currentPlayers = $lobby->players->count();
        $slotsNeeded = $requiredPlayers - $currentPlayers;

        if ($slotsNeeded <= 0) {
            return false; // Lobby already full
        }

        // Get existing player IDs to exclude them from matching
        $existingPlayerIds = $lobby->players->pluck('user_id')->toArray();

        // Get all recent opponents for existing players
        $allRecentOpponents = $this->getRecentOpponentsForPlayers($existingPlayerIds);

        // Exclude both existing players and their recent opponents
        $excludedUserIds = array_unique(array_merge($existingPlayerIds, $allRecentOpponents));

        // Find eligible queue players
        $eligibleQueuePlayers = QueueSlot::where('title_slug', $lobby->title_slug->value)
            ->where('mode_id', $lobby->mode_id)
            ->active()
            ->whereNotIn('user_id', $excludedUserIds)
            ->limit($slotsNeeded)
            ->get();

        // Only fill if we have enough players to complete the lobby
        if ($eligibleQueuePlayers->count() < $slotsNeeded) {
            return false; // Not enough players available
        }

        // Atomically assign all queue players to the lobby
        try {
            DB::transaction(function () use ($lobby, $eligibleQueuePlayers) {
                foreach ($eligibleQueuePlayers as $queueSlot) {
                    // Lock the queue slot to prevent race conditions
                    $slot = QueueSlot::where('id', $queueSlot->id)
                        ->active()
                        ->lockForUpdate()
                        ->first();

                    if (! $slot) {
                        // Slot was already claimed, rollback transaction
                        throw new \Exception('Queue slot no longer available');
                    }

                    // Create lobby player (auto-accepted for queue players)
                    LobbyPlayer::create([
                        'lobby_id' => $lobby->id,
                        'user_id' => $slot->user_id,
                        'client_id' => 1, // Default client
                        'status' => LobbyPlayerStatus::ACCEPTED,
                        'source' => LobbyPlayerSource::QUEUE_MATCHED,
                    ]);

                    // Mark queue slot as matched
                    $slot->update([
                        'status' => QueueSlotStatus::MATCHED,
                        'matched_lobby_id' => $lobby->id,
                    ]);
                }

                // Update lobby status to indicate it's ready to start
                $lobby->update(['status' => 'starting']);
            });

            // After successful transaction, update recent opponents and trigger game start
            $this->updateRecentOpponents($lobby);
            $this->triggerGameStart($lobby);

            return true;

        } catch (\Exception $e) {
            // Transaction rolled back, lobby was not filled
            Log::warning('Failed to fill lobby from queue', [
                'lobby_id' => $lobby->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get all recent opponents for a list of players.
     *
     * @param  array<int>  $playerIds
     * @return array<int>
     */
    private function getRecentOpponentsForPlayers(array $playerIds): array
    {
        $allRecentOpponents = [];

        foreach ($playerIds as $playerId) {
            $recent = Redis::lrange("recent_opponents:{$playerId}", 0, self::RECENT_OPPONENT_LIMIT - 1);
            $allRecentOpponents = array_merge($allRecentOpponents, $recent);
        }

        return array_map('intval', array_unique($allRecentOpponents));
    }

    /**
     * Update recent opponents for all players in the lobby.
     */
    private function updateRecentOpponents(Lobby $lobby): void
    {
        $allPlayerIds = $lobby->players->pluck('user_id')->toArray();

        // For each player, record all other players as recent opponents
        foreach ($allPlayerIds as $i => $playerId) {
            // Only record matches with players we haven't already processed
            // to avoid recording each match twice
            for ($j = $i + 1; $j < count($allPlayerIds); $j++) {
                $opponentId = $allPlayerIds[$j];
                $this->recentOpponentTracker->recordMatch($playerId, $opponentId);
            }
        }
    }

    /**
     * Trigger game start for the lobby if appropriate.
     */
    private function triggerGameStart(Lobby $lobby): void
    {
        // Reload lobby to get fresh data
        $lobby->refresh();

        // Check if lobby is younger than 60 seconds - if so, auto-start immediately
        // If older, it should have gone through ready check flow instead
        $lobbyAge = now()->diffInSeconds($lobby->created_at);

        if ($lobbyAge < self::READY_CHECK_THRESHOLD_SECONDS) {
            // Auto-start the game since lobby was just created and filled from queue
            Log::info('Auto-starting game from queue-filled lobby', [
                'lobby_id' => $lobby->id,
                'lobby_ulid' => $lobby->ulid,
                'lobby_age_seconds' => $lobbyAge,
            ]);

            $this->lobbyGameStarter->startGame($lobby);
        } else {
            // Lobby has been open for 60+ seconds, should use ready check instead
            // This shouldn't happen often, but log it for monitoring
            Log::warning('Lobby filled from queue but is too old for auto-start', [
                'lobby_id' => $lobby->id,
                'lobby_ulid' => $lobby->ulid,
                'lobby_age_seconds' => $lobbyAge,
            ]);
        }
    }

    /**
     * Get the ready check threshold in seconds.
     */
    public function getReadyCheckThreshold(): int
    {
        return self::READY_CHECK_THRESHOLD_SECONDS;
    }
}
