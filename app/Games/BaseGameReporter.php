<?php

declare(strict_types=1);

namespace App\Games;

use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameReporterContract;
use App\Models\Game\Action;
use App\Models\Game\Game;

abstract class BaseGameReporter implements GameReporterContract
{
    public function getPublicStatus(object $gameState): array
    {
        return [];
    }

    public function describeStateChanges(Game $game, Action $action, object $gameState): array
    {
        return [];
    }

    public function formatActionSummary(Action $action): string
    {
        $username = $action->player->user->username ?? 'Player';
        $type = str_replace('_', ' ', $action->action_type->value);

        return sprintf('%s performed %s', $username, $type);
    }

    public function getFinishDetails(Game $game, GameOutcome $outcome, object $gameState): array
    {
        return [
            'reason' => $outcome->details['reason'] ?? 'game_end',
            'winner_id' => $outcome->winnerUlid,
        ];
    }

    public function analyzeOutcome(Game $game, GameOutcome $outcome, object $gameState): array
    {
        return [
            'duration_seconds' => $game->started_at && $game->completed_at
                ? $game->completed_at->diffInSeconds($game->started_at)
                : 0,
            'total_turns' => $game->actions()->count(),
        ];
    }
}
