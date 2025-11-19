<?php

declare(strict_types=1);

namespace App\Services;

use App\Games\GameOutcome;
use App\Models\Game\Action;
use App\Models\Game\Game;

/**
 * Service to enhance game responses with rich context.
 *
 * Provides detailed information about game state changes, available actions,
 * and game outcomes to improve client experience.
 */
class GameResponseEnhancementService
{
    /**
     * Generate rich context for successful action response.
     */
    public function generateActionContext(Game $game, object $gameState, object $mode, Action $actionRecord): array
    {
        $currentPlayerUlid = $gameState->currentPlayerUlid ?? null;
        /** @var \App\Models\Game\Player|null $currentPlayer */
        $currentPlayer = $currentPlayerUlid
            ? $game->players()->where('ulid', $currentPlayerUlid)->first()
            : null;

        $context = [
            'action_summary' => $this->formatActionSummary($actionRecord),
            'state_changes' => $this->detectStateChanges($game, $actionRecord),
            'next_player' => $currentPlayer ? [
                'ulid' => $currentPlayer->ulid,
                'username' => $currentPlayer->user->username,
                'position' => $currentPlayer->position_id,
            ] : null,
            'turn_info' => [
                'current_turn' => $game->turn_number,
                'total_actions' => $game->action_count ?? $game->actions()->count(),
            ],
            'phase' => $gameState->phase->value ?? 'active',
        ];

        // Add available actions for all players
        $availableActions = [];
        foreach ($game->players as $player) {
            $actions = $mode->getAvailableActions($gameState, $player->ulid);
            if (! empty($actions)) {
                $availableActions[$player->ulid] = [
                    'username' => $player->user->username,
                    'position' => $player->position_id,
                    'actions' => $actions,
                ];
            }
        }
        $context['available_actions'] = $availableActions;

        // Add game-specific context
        $context['game_specific'] = $this->getGameSpecificContext($game, $gameState, $actionRecord);

        return $context;
    }

    /**
     * Generate detailed outcome information when game ends.
     */
    public function generateOutcomeDetails(Game $game, GameOutcome $outcome, object $gameState): array
    {
        $details = [
            'outcome_type' => $outcome->isDraw ? 'draw' : 'win',
            'reason' => $outcome->reason,
            'finish_details' => $this->formatFinishDetails($game, $outcome, $gameState),
        ];

        if ($outcome->winnerUlid) {
            /** @var \App\Models\Game\Player|null $winner */
            $winner = $game->players()->where('ulid', $outcome->winnerUlid)->first();
            if ($winner) {
                $details['winner'] = [
                    'ulid' => $winner->ulid,
                    'username' => $winner->user->username,
                    'position' => $winner->position_id,
                ];
            }
        }

        // Add game-specific outcome details
        $details['game_analysis'] = $this->analyzeGameOutcome($game, $outcome, $gameState);

        // Add performance stats
        $details['game_stats'] = $this->calculateGameStats($game);

        return $details;
    }

    /**
     * Format action into human-readable summary.
     */
    protected function formatActionSummary(Action $action): string
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
            'move_piece' => sprintf(
                '%s moved piece from [%d,%d] to [%d,%d]',
                $username,
                $action->action_details['from_row'] ?? 0,
                $action->action_details['from_col'] ?? 0,
                $action->action_details['to_row'] ?? 0,
                $action->action_details['to_col'] ?? 0
            ),
            'jump_piece' => sprintf(
                '%s jumped and captured opponent piece',
                $username
            ),
            'double_jump_piece' => sprintf(
                '%s performed a double jump, capturing 2 pieces',
                $username
            ),
            'triple_jump_piece' => sprintf(
                '%s performed a triple jump, capturing 3 pieces',
                $username
            ),
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
        };
    }

    /**
     * Detect what changed in the game state.
     */
    protected function detectStateChanges(Game $game, Action $action): array
    {
        $changes = [];

        $gameState = $game->game_state;

        // Check for phase transitions
        if (isset($gameState['phase'])) {
            $changes['phase_transition'] = $gameState['phase'];
        }

        // Game-specific state changes
        switch ($game->title_slug->value) {
            case 'checkers':
                if ($this->wasKingPromoted($action)) {
                    $changes[] = 'Piece promoted to King!';
                }
                if ($this->werePiecesCaptured($action)) {
                    $captureCount = $this->countCapturedPieces($action);
                    $changes[] = sprintf('%d opponent piece(s) captured', $captureCount);
                }
                break;

            case 'hearts':
                if (isset($gameState['heartsBroken']) && $gameState['heartsBroken']) {
                    $changes[] = 'Hearts have been broken';
                }
                if ($this->wasTrickCompleted($action, $gameState)) {
                    $changes[] = 'Trick completed';
                }
                break;

            case 'validate-four':
                // Connect 4 specific changes
                break;
        }

        return $changes;
    }

    /**
     * Get game-specific context information.
     */
    protected function getGameSpecificContext(Game $game, object $gameState, Action $action): array
    {
        return match ($game->title_slug->value) {
            'validate-four' => [
                'pieces_played' => $this->countValidateFourPieces($gameState),
                'columns_available' => $this->countAvailableColumns($gameState),
            ],
            'checkers' => [
                'pieces_remaining' => $this->getCheckersPieceCounts($gameState),
                'kings_count' => $this->getCheckersKingCounts($gameState),
            ],
            'hearts' => [
                'round_number' => $gameState->roundNumber ?? 1,
                'hearts_broken' => $gameState->heartsBroken ?? false,
                'current_scores' => $this->getHeartsScores($gameState),
            ],
            default => [],
        };
    }

    /**
     * Format finish details explaining how the game ended.
     */
    protected function formatFinishDetails(Game $game, GameOutcome $outcome, object $gameState): array
    {
        $details = ['reason_text' => $this->getReasonText($outcome->reason)];

        switch ($game->title_slug->value) {
            case 'validate-four':
                if ($outcome->reason === 'four_in_a_row') {
                    $details['winning_sequence'] = $this->findWinningSequence($gameState);
                }
                break;

            case 'checkers':
                $details['final_piece_count'] = $this->getCheckersPieceCounts($gameState);
                break;

            case 'hearts':
                $details['final_scores'] = $this->getHeartsScores($gameState);
                $details['rounds_played'] = $gameState->roundNumber ?? 0;
                break;
        }

        return $details;
    }

    /**
     * Analyze game outcome for insights.
     */
    protected function analyzeGameOutcome(Game $game, GameOutcome $outcome, object $gameState): array
    {
        $analysis = [];

        // Calculate game duration
        $startTime = $game->started_at ?? $game->created_at;
        $endTime = $game->finished_at ?? now();
        $analysis['duration_seconds'] = $startTime->diffInSeconds($endTime);
        $analysis['total_turns'] = $game->turn_number ?? 0;

        // Add game-specific analysis
        switch ($game->title_slug->value) {
            case 'validate-four':
                $analysis['quick_win'] = $game->turn_number < 10;
                break;

            case 'checkers':
                $analysis['dominant_victory'] = $this->wasCheckersVictoryDominant($gameState);
                break;

            case 'hearts':
                if (! empty($outcome->scores)) {
                    $analysis['score_spread'] = max($outcome->scores) - min($outcome->scores);
                    $analysis['shooting_moon_occurred'] = $this->didSomeoneShootMoon($gameState);
                }
                break;
        }

        return $analysis;
    }

    /**
     * Calculate game statistics.
     */
    protected function calculateGameStats(Game $game): array
    {
        $actions = $game->actions()->with('player')->get();

        $stats = [
            'total_actions' => $actions->count(),
            'average_action_time' => $this->calculateAverageActionTime($actions),
        ];

        // Per-player stats
        $playerStats = [];
        foreach ($game->players as $player) {
            $playerActions = $actions->where('player_id', $player->id);
            $playerStats[$player->ulid] = [
                'username' => $player->user->username,
                'actions_taken' => $playerActions->count(),
                'average_response_time' => $this->calculateAverageResponseTime($playerActions),
            ];
        }
        $stats['by_player'] = $playerStats;

        return $stats;
    }

    // Helper methods

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

    protected function wasKingPromoted(Action $action): bool
    {
        // Check if the action resulted in a king promotion
        // This would need to compare game state before/after
        return false; // Placeholder
    }

    protected function werePiecesCaptured(Action $action): bool
    {
        return in_array($action->action_type->value, ['jump_piece', 'double_jump_piece', 'triple_jump_piece']);
    }

    protected function countCapturedPieces(Action $action): int
    {
        return match ($action->action_type->value) {
            'jump_piece' => 1,
            'double_jump_piece' => 2,
            'triple_jump_piece' => 3,
            default => 0,
        };
    }

    protected function wasTrickCompleted(Action $action, array $gameState): bool
    {
        return isset($gameState['currentTrick']) && empty($gameState['currentTrick']);
    }

    protected function countValidateFourPieces(object $gameState): int
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

    protected function getCheckersPieceCounts(object $gameState): array
    {
        $counts = [];
        foreach ($gameState->players ?? [] as $ulid => $player) {
            $counts[$ulid] = $player->piecesRemaining ?? 0;
        }

        return $counts;
    }

    protected function getCheckersKingCounts(object $gameState): array
    {
        $counts = [];
        foreach ($gameState->board ?? [] as $row) {
            foreach ($row as $cell) {
                if ($cell !== null && isset($cell['king']) && $cell['king']) {
                    $playerUlid = $cell['player'] ?? null;
                    if ($playerUlid) {
                        $counts[$playerUlid] = ($counts[$playerUlid] ?? 0) + 1;
                    }
                }
            }
        }

        return $counts;
    }

    protected function getHeartsScores(object $gameState): array
    {
        $scores = [];
        foreach ($gameState->players ?? [] as $ulid => $player) {
            $scores[$ulid] = $player->score ?? 0;
        }

        return $scores;
    }

    protected function getReasonText(string $reason): string
    {
        return match ($reason) {
            'four_in_a_row' => 'Four pieces connected in a row',
            'board_full' => 'Board filled with no winner',
            'no_pieces_remaining' => 'All opponent pieces captured',
            'game_complete' => 'Target score reached',
            'forfeit' => 'Opponent forfeited',
            'timeout' => 'Player timed out',
            default => ucwords(str_replace('_', ' ', $reason)),
        };
    }

    protected function findWinningSequence(object $gameState): ?array
    {
        // This would analyze the board to find the winning 4-in-a-row
        // Placeholder for now
        return null;
    }

    protected function wasCheckersVictoryDominant(object $gameState): bool
    {
        $counts = $this->getCheckersPieceCounts($gameState);
        if (count($counts) < 2) {
            return false;
        }

        $values = array_values($counts);

        return max($values) > min($values) * 2;
    }

    protected function didSomeoneShootMoon(object $gameState): bool
    {
        // Check if any player scored 26 points in a round (shot the moon)
        // This would require round-by-round tracking
        return false; // Placeholder
    }

    protected function calculateAverageActionTime($actions): float
    {
        if ($actions->isEmpty()) {
            return 0;
        }

        $times = [];
        $previousTime = null;

        foreach ($actions as $action) {
            if ($previousTime) {
                $times[] = $action->created_at->diffInSeconds($previousTime);
            }
            $previousTime = $action->created_at;
        }

        return empty($times) ? 0 : array_sum($times) / count($times);
    }

    protected function calculateAverageResponseTime($actions): float
    {
        return $this->calculateAverageActionTime($actions);
    }
}
