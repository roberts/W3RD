<?php

declare(strict_types=1);

namespace App\Games\Hearts;

use App\Enums\GamePhase;
use App\Games\BaseCardGameTitle;
use App\Games\GameOutcome;
use App\Games\ValidationResult;
use App\Interfaces\GameTitleContract;
use App\Models\Game\Action;
use App\Models\Game\Game;
use Carbon\Carbon;

/**
 * Base Hearts game implementation.
 *
 * Implements standard Hearts rules for 4 players.
 */
abstract class BaseHearts extends BaseCardGameTitle implements GameTitleContract
{
    /**
     * Default turn time limit in seconds.
     */
    protected const DEFAULT_TURN_TIME_SECONDS = 30;

    /**
     * Grace period in seconds to account for network latency.
     */
    protected const NETWORK_GRACE_PERIOD_SECONDS = 2;

    /**
     * Default penalty when a turn times out.
     */
    protected const DEFAULT_TIMEOUT_PENALTY = 'forfeit';

    /**
     * Points needed to end the game.
     */
    protected const POINTS_TO_END = 100;

    /**
     * Create initial game state for a new Hearts game.
     *
     * Hearts requires exactly 4 players.
     *
     * @param  string  ...$playerUlids  Player ULIDs (must be exactly 4)
     * @return GameState
     *
     * @throws \InvalidArgumentException If not exactly 4 players provided
     */
    public function createInitialState(string ...$playerUlids): object
    {
        if (count($playerUlids) !== 4) {
            throw new \InvalidArgumentException('Hearts requires exactly 4 players');
        }

        return GameState::createNew(...$playerUlids);
    }

    /**
     * Get the state class name.
     */
    public function getStateClass(): string
    {
        return GameState::class;
    }

    /**
     * Returns the fully qualified class name of the game state object.
     */
    protected function getGameStateClass(): string
    {
        return GameState::class;
    }

    /**
     * Get the action factory class name.
     */
    public function getActionFactory(): string
    {
        return Actions\ActionFactory::class;
    }

    /**
     * Get the structured rules for Hearts.
     */
    public static function getRules(): array
    {
        return [
            'title' => 'Hearts',
            'description' => 'Avoid taking hearts and the Queen of Spades, or "Shoot the Moon" by taking all of them.',
            'sections' => [
                [
                    'title' => 'Setup',
                    'content' => <<<'MARKDOWN'
                    *   Standard 52-card deck.
                    *   Four players, each dealt 13 cards.
                    *   Game continues until one player reaches 100 points.
                    MARKDOWN,
                ],
                [
                    'title' => 'Passing',
                    'content' => <<<'MARKDOWN'
                    *   Before each round, players pass 3 cards:
                        *   Round 1: Pass to the left
                        *   Round 2: Pass to the right
                        *   Round 3: Pass across
                        *   Round 4: No passing (hold)
                    *   Pattern repeats every 4 rounds.
                    MARKDOWN,
                ],
                [
                    'title' => 'Play',
                    'content' => <<<'MARKDOWN'
                    *   Player with 2 of Clubs leads the first trick.
                    *   Players must follow suit if possible.
                    *   Highest card of the led suit wins the trick.
                    *   Winner of trick leads the next trick.
                    *   Hearts cannot be led until "broken" (played when unable to follow suit).
                    MARKDOWN,
                ],
                [
                    'title' => 'Scoring',
                    'content' => <<<'MARKDOWN'
                    *   Each Heart: 1 point
                    *   Queen of Spades: 13 points
                    *   Shooting the Moon: If one player takes all hearts and the Queen, they score 0 and all others score 26 points.
                    *   Game ends when a player reaches 100 points.
                    *   Player with the lowest score wins.
                    MARKDOWN,
                ],
            ],
        ];
    }

    /**
     * Validate a player's action.
     */
    public function validateAction(object $gameState, object $action): ValidationResult
    {
        if (! ($gameState instanceof GameState)) {
            return ValidationResult::invalid(
                'INVALID_STATE_TYPE',
                'Game state must be a GameState instance'
            );
        }

        // Action validation will be implemented with the Standard mode
        return ValidationResult::valid();
    }

    /**
     * Apply a valid action to the game state.
     */
    public function applyAction(object $gameState, object $action): object
    {
        if (! ($gameState instanceof GameState)) {
            return $gameState;
        }

        // Handle different action types
        if ($action instanceof Actions\PassCards) {
            return $this->applyPassCards($gameState, $action);
        }

        if ($action instanceof Actions\PlayCard) {
            return $this->applyPlayCard($gameState, $action);
        }

        if ($action instanceof Actions\ClaimRemainingTricks) {
            return $this->applyClaimRemainingTricks($gameState, $action);
        }

        return $gameState;
    }

    /**
     * Apply pass cards action.
     *
     * This action is coordinated - it just validates and returns unchanged state.
     * The controller will detect when all players have submitted and call processPassCards.
     */
    protected function applyPassCards(GameState $gameState, Actions\PassCards $action): GameState
    {
        // Coordinated action - just validate, don't modify state
        // Actual card exchange happens in processPassCards after all players submit
        return $gameState;
    }

    /**
     * Process coordinated pass cards actions.
     *
     * Called by the controller when all players have submitted their pass_cards actions.
     * Performs the actual card exchange and advances to ACTIVE phase.
     *
     * @param  GameState  $gameState  Current game state
     * @param  \Illuminate\Support\Collection  $passActions  All pass_cards actions for this round
     * @return GameState Updated game state with exchanged cards
     */
    public function processPassCards(GameState $gameState, $passActions): GameState
    {
        // Extract card passes from action_details
        $pendingPasses = [];
        foreach ($passActions as $passAction) {
            $playerUlid = $passAction->player->ulid;
            $pendingPasses[$playerUlid] = $passAction->action_details['cards'] ?? [];
        }

        // Perform card exchange
        $playerUlids = array_keys($gameState->players);
        $hands = $gameState->hands;
        $passingDirection = $this->getPassingDirection($gameState->roundNumber);

        foreach ($playerUlids as $index => $playerUlid) {
            // Remove the passed cards from this player's hand
            $cardsToPass = $pendingPasses[$playerUlid] ?? [];
            foreach ($cardsToPass as $card) {
                $cardIndex = array_search($card, $hands[$playerUlid]);
                if ($cardIndex !== false) {
                    array_splice($hands[$playerUlid], $cardIndex, 1);
                }
            }

            // Determine who receives this player's cards
            $recipientIndex = match ($passingDirection) {
                'left' => ($index + 1) % 4,
                'right' => ($index + 3) % 4,
                'across' => ($index + 2) % 4,
                'hold' => $index,
                default => $index,  // Shouldn't happen, but handle anyway
            };
            $recipientUlid = $playerUlids[$recipientIndex];

            // Add the cards to the recipient's hand
            if ($passingDirection !== 'hold') {
                $hands[$recipientUlid] = array_merge($hands[$recipientUlid], $cardsToPass);
            }
        }

        // Find the player with the 2 of Clubs to start the first trick
        $trickLeader = null;
        foreach ($hands as $ulid => $hand) {
            if (in_array('C2', $hand)) {
                $trickLeader = $ulid;
                break;
            }
        }

        // Move to ACTIVE phase with exchanged hands
        return new GameState(
            players: $gameState->players,
            currentPlayerUlid: $trickLeader ?? $playerUlids[0],
            winnerUlid: $gameState->winnerUlid,
            phase: GamePhase::ACTIVE,
            status: $gameState->status,
            roundNumber: $gameState->roundNumber,
            hands: $hands,
            currentTrick: [],
            trickLeaderUlid: $trickLeader ?? $playerUlids[0],
            heartsBroken: false,
            isDraw: $gameState->isDraw,
        );
    }

    /**
     * Get the passing direction for a given round number.
     */
    protected function getPassingDirection(int $roundNumber): string
    {
        return match (($roundNumber - 1) % 4) {
            0 => 'left',
            1 => 'right',
            2 => 'across',
            3 => 'hold',
            default => 'left',  // Fallback, shouldn't happen with modulo 4
        };
    }

    /**
     * Apply play card action.
     */
    protected function applyPlayCard(GameState $gameState, Actions\PlayCard $action): GameState
    {
        if ($gameState->currentPlayerUlid === null) {
            return $gameState;
        }

        // Remove card from player's hand
        $hands = $gameState->hands;
        $playerHand = $hands[$gameState->currentPlayerUlid] ?? [];
        $cardIndex = array_search($action->card, $playerHand);

        if ($cardIndex !== false) {
            array_splice($playerHand, $cardIndex, 1);
            $hands[$gameState->currentPlayerUlid] = $playerHand;
        }

        // Add card to current trick
        $currentTrick = $gameState->currentTrick;
        $currentTrick[$gameState->currentPlayerUlid] = $action->card;

        // Check if hearts were broken
        $heartsBroken = $gameState->heartsBroken || $action->isHeart();

        // Determine trick leader (first player in trick if not set)
        $trickLeaderUlid = $gameState->trickLeaderUlid ?? $gameState->currentPlayerUlid;

        // Check if trick is complete (all 4 players have played)
        if (count($currentTrick) === 4) {
            // Determine trick winner
            $trickWinnerUlid = $this->determineTrickWinner($currentTrick, $trickLeaderUlid);

            // Calculate points from this trick
            $trickPoints = $this->calculateTrickPoints($currentTrick);

            // Update player scores
            $players = $gameState->players;
            foreach ($players as $ulid => $player) {
                if ($ulid === $trickWinnerUlid) {
                    $players[$ulid] = new PlayerState(
                        ulid: $player->ulid,
                        position: $player->position,
                        score: $player->score + $trickPoints,
                    );
                }
            }

            // Clear trick and set winner as next leader
            return new GameState(
                players: $players,
                currentPlayerUlid: $trickWinnerUlid,
                winnerUlid: $gameState->winnerUlid,
                phase: $gameState->phase,
                status: $gameState->status,
                roundNumber: $gameState->roundNumber,
                hands: $hands,
                currentTrick: [], // Clear trick
                trickLeaderUlid: $trickWinnerUlid,
                heartsBroken: $heartsBroken,
                isDraw: $gameState->isDraw,
            );
        }

        // Move to next player
        return new GameState(
            players: $gameState->players,
            currentPlayerUlid: $this->getNextPlayerUlid($gameState),
            winnerUlid: $gameState->winnerUlid,
            phase: $gameState->phase,
            status: $gameState->status,
            roundNumber: $gameState->roundNumber,
            hands: $hands,
            currentTrick: $currentTrick,
            trickLeaderUlid: $trickLeaderUlid,
            heartsBroken: $heartsBroken,
            isDraw: $gameState->isDraw,
        );
    }

    /**
     * Apply claim remaining tricks action.
     */
    protected function applyClaimRemainingTricks(GameState $gameState, Actions\ClaimRemainingTricks $action): GameState
    {
        // This would analyze remaining cards and award points
        // For now, just return the state
        return $gameState;
    }

    /**
     * Determine which player won the trick.
     */
    protected function determineTrickWinner(array $trick, string $leadPlayerUlid): string
    {
        $leadCard = $trick[$leadPlayerUlid];
        $leadSuit = $leadCard[0];

        $highestCard = $leadCard;
        $winnerUlid = $leadPlayerUlid;

        foreach ($trick as $playerUlid => $card) {
            $suit = $card[0];

            // Only cards of the led suit can win
            if ($suit === $leadSuit) {
                if ($this->compareCards($card, $highestCard, $leadSuit) > 0) {
                    $highestCard = $card;
                    $winnerUlid = $playerUlid;
                }
            }
        }

        return $winnerUlid;
    }

    /**
     * Compare two cards of the same suit.
     * Returns positive if card1 > card2, negative if card1 < card2, 0 if equal.
     */
    protected function compareCards(string $card1, string $card2, string $suit): int
    {
        $rankOrder = ['2', '3', '4', '5', '6', '7', '8', '9', 'T', 'J', 'Q', 'K', 'A'];

        $rank1 = substr($card1, 1);
        $rank2 = substr($card2, 1);

        $value1 = array_search($rank1, $rankOrder);
        $value2 = array_search($rank2, $rankOrder);

        return $value1 - $value2;
    }

    /**
     * Calculate points from a completed trick.
     */
    protected function calculateTrickPoints(array $trick): int
    {
        $points = 0;

        foreach ($trick as $card) {
            $suit = $card[0];
            $rank = substr($card, 1);

            // Hearts are worth 1 point each
            if ($suit === 'H') {
                $points += 1;
            }

            // Queen of Spades is worth 13 points
            if ($card === 'SQ') {
                $points += 13;
            }
        }

        return $points;
    }

    /**
     * Get the next player's ULID.
     */
    protected function getNextPlayerUlid(GameState $gameState): string
    {
        $playerUlids = array_keys($gameState->players);
        $currentIndex = array_search($gameState->currentPlayerUlid, $playerUlids);

        if ($currentIndex === false) {
            return $playerUlids[0];
        }

        $nextIndex = ($currentIndex + 1) % count($playerUlids);

        return $playerUlids[$nextIndex];
    }

    /**
     * Check if the game has ended.
     */
    public function checkEndCondition(object $gameState): GameOutcome
    {
        if (! ($gameState instanceof GameState)) {
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

            foreach ($gameState->players as $player) {
                if ($player->score < $lowestScore) {
                    $lowestScore = $player->score;
                    $winnerUlid = $player->ulid;
                }
            }

            return GameOutcome::win($winnerUlid);
        }

        return GameOutcome::inProgress();
    }

    /**
     * Get available actions for a player.
     */
    public function getAvailableActions(object $gameState, string $playerUlid): array
    {
        // Will be implemented with game mode
        return [];
    }

    /**
     * Get the time limit in seconds.
     */
    public function getTimelimit(): int
    {
        return self::DEFAULT_TURN_TIME_SECONDS;
    }

    /**
     * Get the action deadline.
     */
    public function getActionDeadline(object $gameState, Game $game): Carbon
    {
        /** @var Action|null $lastAction */
        $lastAction = $game->actions()->latest()->first();
        $lastActionTime = $lastAction ? $lastAction->created_at : $game->created_at;

        return $lastActionTime->addSeconds(
            $this->getTimelimit() + self::NETWORK_GRACE_PERIOD_SECONDS
        );
    }

    /**
     * Get the timeout penalty.
     */
    public function getTimeoutPenalty(): string
    {
        return self::DEFAULT_TIMEOUT_PENALTY;
    }
}
