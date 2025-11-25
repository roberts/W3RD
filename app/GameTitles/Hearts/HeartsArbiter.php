<?php

declare(strict_types=1);

namespace App\GameTitles\Hearts;

use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameArbiterContract;

class HeartsArbiter implements GameArbiterContract
{
    protected const POINTS_TO_END = 100;

    public function checkWinCondition(object $gameState): GameOutcome
    {
        if (! ($gameState instanceof HeartsTable)) {
            return GameOutcome::inProgress();
        }

        // Check if any player has reached 100 points
        $maxScore = 0;
        foreach ($gameState->players as $player) {
            if ($player->score > $maxScore) {
                $maxScore = $player->score;
            }
        }

        if ($maxScore >= self::POINTS_TO_END) {
            // Find player with lowest score (they win)
            $lowestScore = PHP_INT_MAX;
            $winnerUlid = null;
            $scores = [];

            foreach ($gameState->players as $player) {
                $scores[$player->ulid] = $player->score;
                if ($player->score < $lowestScore) {
                    $lowestScore = $player->score;
                    $winnerUlid = $player->ulid;
                }
            }

            return GameOutcome::win($winnerUlid, null, 'score_limit_reached', [
                'scores' => $scores,
                'max_score' => $maxScore,
            ]);
        }

        return GameOutcome::inProgress();
    }
}
