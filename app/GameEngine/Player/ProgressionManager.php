<?php

namespace App\GameEngine\Player;

class ProgressionManager
{
    /**
     * Calculate XP required to reach next level.
     */
    public function calculateXpToNextLevel(int $currentLevel): int
    {
        // Simple exponential formula: base * (1.5 ^ level)
        $baseXp = 100;
        $multiplier = 1.5;

        $currentLevelXp = (int) round($baseXp * pow($multiplier, $currentLevel));
        $nextLevelXp = (int) round($baseXp * pow($multiplier, $currentLevel + 1));

        return $nextLevelXp - $currentLevelXp;
    }

    /**
     * Calculate level from total XP.
     */
    public function calculateLevelFromXp(int $totalXp): int
    {
        $level = 0;
        $xpForLevel = 0;

        while ($xpForLevel <= $totalXp) {
            $level++;
            $xpForLevel += $this->calculateXpToNextLevel($level - 1);
        }

        return max(1, $level - 1);
    }

    /**
     * Award XP to user for completing a game.
     */
    public function awardXp(int $userId, string $gameTitle, int $xpAmount, string $reason): void
    {
        // TODO: Implement XP awarding logic
        // - Update user_title_levels table
        // - Check for level up
        // - Create XP transaction record
        // - Trigger LevelUp event if applicable
    }
}
