<?php

declare(strict_types=1);

namespace App\Services;

use App\Games\BaseGameTitle;
use App\Games\GameOutcome;
use App\Models\Game\Action;
use App\Models\Game\Game;
use App\Providers\GameServiceProvider;

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
    public function generateActionContext(Game $game, object $gameState, BaseGameTitle $mode, Action $actionRecord): array
    {
        $currentPlayerUlid = $gameState->currentPlayerUlid ?? null;
        /** @var \App\Models\Game\Player|null $currentPlayer */
        $currentPlayer = $currentPlayerUlid
            ? $game->players()->where('ulid', $currentPlayerUlid)->first()
            : null;

        $context = [
            'action_summary' => $mode->formatActionSummary($actionRecord),
            'state_changes' => $mode->describeStateChanges($game, $actionRecord, $gameState),
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
        $context['game_specific'] = $mode->getPublicStatus($gameState);

        return $context;
    }

    /**
     * Generate detailed outcome information when game ends.
     */
    public function generateOutcomeDetails(Game $game, GameOutcome $outcome, object $gameState): array
    {
        $mode = GameServiceProvider::getMode($game);

        $details = [
            'outcome_type' => $outcome->type?->value,
            'reason' => $outcome->details['reason'] ?? null,
            'finish_details' => $mode->getFinishDetails($game, $outcome, $gameState),
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
        $details['game_analysis'] = $mode->analyzeOutcome($game, $outcome, $gameState);

        // Add performance stats
        $details['game_stats'] = $this->calculateGameStats($game);

        return $details;
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
