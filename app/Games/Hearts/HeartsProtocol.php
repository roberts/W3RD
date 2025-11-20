<?php

declare(strict_types=1);

namespace App\Games\Hearts;

use App\Enums\GamePhase;
use App\Exceptions\InvalidGameConfigurationException;
use App\GameEngine\Interfaces\GameTitleContract;
use App\Games\BaseCardGameTitle;
use App\Games\Hearts\Actions\HeartsActionMapper;
use App\Models\Game\Game;

/**
 * Base Hearts game implementation.
 *
 * Implements standard Hearts rules for 4 players.
 */
abstract class HeartsProtocol extends BaseCardGameTitle implements GameTitleContract
{
    protected const POINTS_TO_END = 100;

    abstract protected function getGameConfig(): HeartsConfig;

    abstract protected function getArbiter(): HeartsArbiter;

    abstract protected function getReporter(): HeartsReporter;

    public function createInitialState(string ...$playerUlids): object
    {
        if (count($playerUlids) !== 4) {
            throw new InvalidGameConfigurationException(
                'Hearts requires exactly 4 players',
                'hearts',
                ['player_count' => count($playerUlids)]
            );
        }

        return HeartsTable::createNew(...$playerUlids);
    }

    public function getStateClass(): string
    {
        return HeartsTable::class;
    }

    protected function getGameStateClass(): string
    {
        return HeartsTable::class;
    }

    public function getActionMapper(): string
    {
        return HeartsActionMapper::class;
    }

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

    public function processPassCards(HeartsTable $gameState, $passActions): HeartsTable
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
        return new HeartsTable(
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

    protected function compareCards(string $card1, string $card2, string $suit): int
    {
        $rankOrder = ['2', '3', '4', '5', '6', '7', '8', '9', 'T', 'J', 'Q', 'K', 'A'];

        $rank1 = substr($card1, 1);
        $rank2 = substr($card2, 1);

        $value1 = array_search($rank1, $rankOrder);
        $value2 = array_search($rank2, $rankOrder);

        return $value1 - $value2;
    }

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

    protected function getNextPlayerUlid(HeartsTable $gameState): string
    {
        $playerUlids = array_keys($gameState->players);
        $currentIndex = array_search($gameState->currentPlayerUlid, $playerUlids);

        if ($currentIndex === false) {
            return $playerUlids[0];
        }

        $nextIndex = ($currentIndex + 1) % count($playerUlids);

        return $playerUlids[$nextIndex];
    }

    public function getAvailableActions(object $gameState, string $playerUlid): array
    {
        // Will be implemented with game mode
        return [];
    }
}
