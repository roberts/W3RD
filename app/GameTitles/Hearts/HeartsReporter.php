<?php

declare(strict_types=1);

namespace App\GameTitles\Hearts;

use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameReporterContract;
use App\GameTitles\BaseGameReporter;
use App\Models\Game\Action;
use App\Models\Game\Game;

class HeartsReporter extends BaseGameReporter implements GameReporterContract
{
    public function getPublicStatus(object $gameState): array
    {
        return [
            'round_number' => $gameState->roundNumber ?? 1,
            'hearts_broken' => $gameState->heartsBroken ?? false,
            'current_scores' => $this->getScores($gameState),
        ];
    }

    public function describeStateChanges(Game $game, Action $action, object $gameState): array
    {
        $changes = parent::describeStateChanges($game, $action, $gameState);

        if (isset($gameState->heartsBroken) && $gameState->heartsBroken) {
            $changes[] = 'Hearts have been broken';
        }
        if ($this->wasTrickCompleted($action, $gameState)) {
            $changes[] = 'Trick completed';
        }

        return $changes;
    }

    public function formatActionSummary(Action $action): string
    {
        $username = $action->player->user->username;

        return match ($action->action_type->value) {
            'play_card' => sprintf(
                '%s played %s',
                $username,
                $this->formatCard($action->action_details['card'] ?? '')
            ),
            'pass_cards' => sprintf(
                '%s passed %d cards',
                $username,
                count($action->action_details['cards'] ?? [])
            ),
            'claim_remaining_tricks', 'pass', 'draw_card', 'bid' => sprintf(
                '%s performed %s',
                $username,
                $action->action_type->value
            ),
            default => parent::formatActionSummary($action),
        };
    }

    public function getFinishDetails(Game $game, GameOutcome $outcome, object $gameState): array
    {
        $details = parent::getFinishDetails($game, $outcome, $gameState);

        $details['final_scores'] = $this->getScores($gameState);
        $details['rounds_played'] = $gameState->roundNumber ?? 0;

        $reason = $outcome->details['reason'] ?? null;
        if ($reason === 'game_complete') {
            $details['reason_text'] = 'Target score reached';
        }

        return $details;
    }

    public function analyzeOutcome(Game $game, GameOutcome $outcome, object $gameState): array
    {
        $analysis = parent::analyzeOutcome($game, $outcome, $gameState);

        if (! empty($outcome->details['scores'])) {
            $analysis['score_spread'] = max($outcome->details['scores']) - min($outcome->details['scores']);
            $analysis['shooting_moon_occurred'] = $this->didSomeoneShootMoon($gameState);
        }

        return $analysis;
    }

    // Helpers

    protected function getScores(object $gameState): array
    {
        $scores = [];
        foreach ($gameState->players ?? [] as $ulid => $player) {
            $scores[$ulid] = $player->score ?? 0;
        }

        return $scores;
    }

    protected function formatCard(string $card): string
    {
        if (strlen($card) < 2) {
            return $card;
        }

        $suits = ['C' => '♣', 'D' => '♦', 'H' => '♥', 'S' => '♠'];
        $ranks = ['T' => '10', 'J' => 'Jack', 'Q' => 'Queen', 'K' => 'King', 'A' => 'Ace'];

        $suit = $suits[$card[0]] ?? $card[0];
        $rank = $ranks[substr($card, 1)] ?? substr($card, 1);

        return $rank.$suit;
    }

    protected function wasTrickCompleted(Action $action, object $gameState): bool
    {
        // Check if current trick is empty (meaning it was just cleared)
        // This logic might need adjustment based on how state is updated
        return isset($gameState->currentTrick) && empty($gameState->currentTrick);
    }

    protected function didSomeoneShootMoon(object $gameState): bool
    {
        // Placeholder as per service
        return false;
    }
}
