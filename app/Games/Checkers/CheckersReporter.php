<?php

declare(strict_types=1);

namespace App\Games\Checkers;

use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameReporterContract;
use App\Models\Game\Action;
use App\Models\Game\Game;

class CheckersReporter implements GameReporterContract
{
    public function getPublicStatus(object $gameState): array
    {
        if (! ($gameState instanceof CheckersBoard)) {
            return [];
        }

        return [
            'pieces_remaining' => array_map(
                fn ($p) => $p->piecesRemaining,
                $gameState->players
            ),
            'is_draw' => $gameState->isDraw,
        ];
    }

    public function describeStateChanges(Game $game, Action $action, object $gameState): array
    {
        // Basic implementation - can be expanded
        return [];
    }

    public function formatActionSummary(Action $action): string
    {
        $type = $action->action_type->value;
        $data = $action->action_details;

        return match ($type) {
            'move_piece' => "Moved piece from ({$data['from_row']}, {$data['from_col']}) to ({$data['to_row']}, {$data['to_col']})",
            'jump_piece' => "Jumped piece from ({$data['from_row']}, {$data['from_col']}) to ({$data['to_row']}, {$data['to_col']})",
            'double_jump_piece' => 'Double jumped',
            'triple_jump_piece' => 'Triple jumped',
            default => "Performed action: {$type}",
        };
    }

    public function getFinishDetails(Game $game, GameOutcome $outcome, object $gameState): array
    {
        return [
            'reason' => $outcome->details['reason'] ?? null,
            'winner' => $outcome->winnerUlid,
        ];
    }

    public function analyzeOutcome(Game $game, GameOutcome $outcome, object $gameState): array
    {
        return [];
    }
}
