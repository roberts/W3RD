<?php

namespace App\GameEngine\Lifecycle\Conclusion;

use App\Enums\GameAttributes\GameDynamic;
use App\Enums\GameStatus;
use App\Enums\OutcomeType;
use App\GameEngine\Events\GameCompleted;
use App\GameEngine\GameOutcome;
use App\GameEngine\ModeRegistry;
use App\Models\Games\Game;
use App\Models\Games\Player;

class ConclusionManager
{
    public function __construct(
        protected ModeRegistry $modeRegistry
    ) {}

    public function determineOutcome(Game $game): void
    {
        if ($game->status->isFinished()) {
            return;
        }

        $title = $game->title_slug;

        $outcome = match ($title->getDynamic()) {
            GameDynamic::ELIMINATION => $this->handleElimination($game),
            GameDynamic::ONE_VS_ONE, GameDynamic::FREE_FOR_ALL, GameDynamic::TEAM_BASED => $this->handleRuleBased($game),
            default => null,
        };

        if ($outcome && $outcome->isFinished) {
            $this->processGameCompletion($game, $outcome);
        }
    }

    private function processGameCompletion(Game $game, GameOutcome $outcome): void
    {
        $game->status = GameStatus::COMPLETED;
        $game->outcome_type = $outcome->type;
        $game->outcome_details = $outcome->details;
        $game->completed_at = now();

        $gameState = $game->game_state;

        if ($outcome->winnerUlid) {
            /** @var Player|null $winner */
            $winner = $game->players()->where('ulid', $outcome->winnerUlid)->first();
            if ($winner) {
                $game->winner_id = $winner->id;
                $game->winner_position = $winner->position_id;
            }
            $gameState['winner_ulid'] = $outcome->winnerUlid;
        }

        if ($outcome->type === OutcomeType::DRAW) {
            $gameState['is_draw'] = true;
        }

        // Store rankings and scores if provided
        if (! empty($outcome->details['rankings'])) {
            $gameState['final_rankings'] = $outcome->details['rankings'];
            $gameState['final_scores'] = $outcome->details['scores'] ?? [];
        }

        $game->game_state = $gameState;
        $game->save();

        event(new GameCompleted(
            game: $game,
            winnerUlid: $outcome->winnerUlid,
            isDraw: $outcome->type === OutcomeType::DRAW,
            outcomeDetails: $outcome->details,
        ));
    }

    private function handleElimination(Game $game): ?GameOutcome
    {
        // Logic to determine if only one player remains
        $activePlayers = array_filter($game->game_state['players'] ?? [], fn ($player) => ! ($player['is_out'] ?? false));

        if (count($activePlayers) === 1) {
            $winner = reset($activePlayers);

            return new GameOutcome(
                isFinished: true,
                winnerUlid: $winner['ulid'],
                details: ['reason' => 'Last player remaining.']
            );
        }

        return null;
    }

    private function handleRuleBased(Game $game): ?GameOutcome
    {
        // Load the game mode to get the arbiter
        $mode = $this->modeRegistry->resolve($game);

        // Get the arbiter from the mode
        $arbiter = $mode->getArbiter();

        // Get the current game state
        $stateClass = $mode->getStateClass();
        $gameState = $stateClass::fromArray($game->game_state ?? []);

        // Check win condition using the arbiter
        return $arbiter->checkWinCondition($gameState);
    }
}
