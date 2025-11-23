<?php

namespace App\Services\Account;

use App\Enums\GameStatus;
use App\Models\Auth\User;

class UserStatisticsService
{
    /**
     * Get game statistics for a user.
     */
    /**
     * @return array<string, mixed>
     */
    public function getGameStatistics(User $user): array
    {
        $totalGames = $user->players()->count();
        $wins = $this->getWins($user);
        $losses = $this->getLosses($user);

        return [
            'total_games' => $totalGames,
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $totalGames - $wins - $losses,
        ];
    }

    /**
     * Calculate win rate as a percentage.
     */
    public function getWinRate(User $user): float
    {
        $totalGames = $user->players()->count();

        if ($totalGames === 0) {
            return 0.0;
        }

        $wins = $this->getWins($user);

        return round(($wins / $totalGames) * 100, 2);
    }

    /**
     * Get total points from the points ledger.
     */
    public function getTotalPoints(User $user): int
    {
        return $user->points()->sum('change');
    }

    /**
     * Get ELO ratings per game (placeholder for future implementation).
     */
    /**
     * @return array<string, int>
     */
    public function getEloRatings(User $user): array
    {
        // TODO: Implement ELO ratings per game
        return [];
    }

    /**
     * Get global rank (placeholder for future implementation).
     */
    public function getGlobalRank(User $user): ?int
    {
        // TODO: Implement global rank calculation
        return null;
    }

    /**
     * Count wins: games where the player's ID matches the winner_id.
     */
    protected function getWins(User $user): int
    {
        return $user->players()->whereHas('game', function ($query) {
            $query->whereColumn('winner_id', 'players.id');
        })->count();
    }

    /**
     * Count losses: completed games where player didn't win.
     */
    protected function getLosses(User $user): int
    {
        return $user->players()->whereHas('game', function ($query) {
            $query->where('status', GameStatus::COMPLETED)
                ->where(function ($q) {
                    $q->whereColumn('winner_id', '!=', 'players.id')
                        ->orWhereNull('winner_id');
                });
        })->count();
    }
}
