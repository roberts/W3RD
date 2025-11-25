<?php

declare(strict_types=1);

namespace App\GameTitles\ConnectFour;

use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameReporterContract;
use App\Models\Games\Action;
use App\Models\Games\Game;

class ConnectFourReporter implements GameReporterContract
{
    /**
     * @return array<string, mixed>
     */
    public function getPublicStatus(object $gameState): array
    {
        return [
            'pieces_played' => $this->countPieces($gameState),
            'columns_available' => $this->countAvailableColumns($gameState),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function describeStateChanges(Game $game, Action $action, object $gameState): array
    {
        $changes = [];

        // Check for phase transitions
        if (isset($gameState->phase) && isset($game->game_state['phase']) && $gameState->phase->value !== $game->game_state['phase']) {
            $changes['phase_transition'] = $gameState->phase->value;
        }

        return $changes;
    }

    public function formatActionSummary(Action $action): string
    {
        $username = $action->player->user->username;

        return match ($action->action_type->value) {
            'drop_piece' => sprintf(
                '%s dropped a piece in column %d',
                $username,
                ($action->action_details['column'] ?? 0) + 1
            ),
            'pop_out' => sprintf(
                '%s popped out from column %d',
                $username,
                ($action->action_details['column'] ?? 0) + 1
            ),
            default => sprintf('%s performed %s', $username, $action->action_type->value),
        };
    }

    public function getFinishDetails(Game $game, GameOutcome $outcome, object $gameState): array
    {
        $reason = $outcome->details['reason'] ?? null;
        $details = [
            'reason_text' => $reason ? ucwords(str_replace('_', ' ', $reason)) : null,
        ];

        if ($reason === 'four_in_a_row') {
            $details['winning_sequence'] = $this->findWinningSequence($gameState);
            $details['reason_text'] = 'Four pieces connected in a row';
        } elseif ($reason === 'board_full') {
            $details['reason_text'] = 'Board filled with no winner';
        }

        return $details;
    }

    public function analyzeOutcome(Game $game, GameOutcome $outcome, object $gameState): array
    {
        $startTime = $game->started_at ?? $game->created_at;
        $endTime = $game->completed_at ?? now();

        return [
            'duration_seconds' => $startTime->diffInSeconds($endTime),
            'total_turns' => $game->turn_number ?? 0,
            'quick_win' => ($game->turn_number ?? 0) < 10,
        ];
    }

    // Helpers

    protected function countPieces(object $gameState): int
    {
        $count = 0;
        foreach ($gameState->board ?? [] as $row) {
            foreach ($row as $cell) {
                if ($cell !== null) {
                    $count++;
                }
            }
        }

        return $count;
    }

    protected function countAvailableColumns(object $gameState): int
    {
        $available = 0;
        for ($col = 0; $col < ($gameState->columns ?? 7); $col++) {
            if (method_exists($gameState, 'getLowestEmptyRow') && $gameState->getLowestEmptyRow($col) !== null) {
                $available++;
            }
        }

        return $available;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findWinningSequence(object $gameState): ?array
    {
        // Placeholder - could duplicate logic from WinEvaluator or share it
        return null;
    }
}
