<?php

namespace App\GameEngine\Lifecycle\Conclusion;

use App\GameEngine\Player\ProgressionManager;
use App\Models\Game\Game;
use App\Models\Game\Player;

class OutcomeProcessor
{
    public function __construct(protected ProgressionManager $progressionManager) {}

    /**
     * Calculate and store XP and rewards for game completion.
     */
    public function process(Game $game): void
    {
        if (! $game->isCompleted()) {
            return;
        }

        $xpAwarded = [];
        $rewards = [];
        $finalScores = [];

        // Get base XP from config
        $baseXp = (int) config('protocol.game.base_xp', 100);
        $winBonusXp = (int) config('protocol.game.win_bonus_xp', 50);

        foreach ($game->players as $player) {
            $playerXp = $baseXp;
            $playerRewards = [];

            // Add win bonus
            if ($game->winner_id === $player->user_id) {
                $playerXp += $winBonusXp;
                $playerRewards[] = 'win_bonus';
            }

            // Store scores if available in game state
            if (isset($game->game_state['scores'][$player->ulid])) {
                $finalScores[$player->ulid] = $game->game_state['scores'][$player->ulid];
            }

            $xpAwarded[$player->ulid] = $playerXp;
            $rewards[$player->ulid] = $playerRewards;

            // Delegate XP awarding to ProgressionManager
            $this->progressionManager->awardXp(
                $player->user_id,
                $game->title_slug->value,
                $playerXp,
                'game_completion'
            );
        }

        // Update game with calculated outcome data
        $game->update([
            'final_scores' => $finalScores,
            'xp_awarded' => $xpAwarded,
            'rewards' => $rewards,
        ]);
    }

    /**
     * Get player statistics for a completed game.
     */
    public function getPlayerStats(Game $game, Player $player): array
    {
        return [
            'xp_earned' => $game->xp_awarded[$player->ulid] ?? 0,
            'rewards' => $game->rewards[$player->ulid] ?? [],
            'final_score' => $game->final_scores[$player->ulid] ?? null,
            'is_winner' => $game->winner_id === $player->user_id,
            'outcome_type' => $game->outcome_type?->value,
        ];
    }
}
