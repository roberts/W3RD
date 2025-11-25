<?php

declare(strict_types=1);

namespace App\GameEngine\Lifecycle\Conclusion;

use App\Models\Games\Game;
use App\Models\Games\Player;

/**
 * Calculates final rankings for multiplayer games.
 */
class RankingCalculator
{
    /**
     * Calculate rankings for a completed game.
     *
     * @param  Game  $game  The completed game
     * @param  array<string, int>  $scores  Player scores indexed by ULID
     * @return list<array{player_ulid: string, position: int, score: int}> Rankings with player ULIDs and positions
     */
    public function calculate(Game $game, array $scores): array
    {
        // Sort players by score (highest first)
        arsort($scores);

        $rankings = [];
        $position = 1;
        $previousScore = null;
        $playersWithSameRank = 0;

        foreach ($scores as $playerUlid => $score) {
            // Handle ties - players with the same score get the same rank
            if ($previousScore !== null && $score < $previousScore) {
                $position += $playersWithSameRank;
                $playersWithSameRank = 1;
            } else {
                $playersWithSameRank++;
            }

            $rankings[] = [
                'player_ulid' => $playerUlid,
                'position' => $position,
                'score' => $score,
            ];

            $previousScore = $score;
        }

        return $rankings;
    }

    /**
     * Calculate rankings based on custom comparison logic.
     *
     * @param  Game  $game  The game instance
     * @param  array<int, mixed>  $players  Array of player data
     * @param  callable  $comparator  Function to compare two players
     * @return array<int, array<string, mixed>> Rankings
     */
    public function calculateCustom(Game $game, array $players, callable $comparator): array
    {
        usort($players, $comparator);

        $rankings = [];
        foreach ($players as $index => $player) {
            $rankings[] = [
                'player_ulid' => $player['ulid'] ?? $player->ulid,
                'position' => $index + 1,
                'data' => $player,
            ];
        }

        return $rankings;
    }

    /**
     * Get the winner from rankings.
     *
     * @param  array<int, array<string, mixed>>  $rankings  Rankings array
     * @return string|null Winner's ULID or null if draw
     */
    public function getWinner(array $rankings): ?string
    {
        if (empty($rankings)) {
            return null;
        }

        // If multiple players have position 1, it's a draw
        $topRanked = array_filter($rankings, fn ($rank) => $rank['position'] === 1);

        if (count($topRanked) > 1) {
            return null; // Draw
        }

        return $rankings[0]['player_ulid'];
    }
}
