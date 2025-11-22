<?php

declare(strict_types=1);

namespace App\Games;

use App\Enums\GameAttributes\GamePacing;
use App\Enums\GameAttributes\GameSequence;
use App\Enums\GameAttributes\GameVisibility;
use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameArbiterContract;
use App\GameEngine\Interfaces\GameReporterContract;
use App\GameEngine\Traits\Pacing\SynchronousPacing;
use App\GameEngine\Traits\Sequence\SequentialTurns;
use App\GameEngine\Traits\TimerExpired\ForfeitOnTimerExpired;
use App\GameEngine\Traits\Visibility\HiddenInformation;
use App\Models\Game\Action;
use App\Models\Game\Game;

/**
 * Base class for card game titles.
 *
 * Provides shared functionality for card-based games like Hearts, Spades, Poker.
 * Extends BaseGameTitle with card-specific helper methods for deck management,
 * shuffling, dealing, and card comparison.
 */
abstract class BaseCardGameTitle extends BaseGameTitle
{
    use ForfeitOnTimerExpired;
    use HiddenInformation;
    use SequentialTurns;
    use SynchronousPacing;

    // Game Attribute Implementations - these are now pure metadata
    public static function getPacing(): GamePacing
    {
        return GamePacing::TURN_BASED_SYNC;
    }

    public static function getSequence(): GameSequence
    {
        return GameSequence::SEQUENTIAL;
    }

    public static function getVisibility(): GameVisibility
    {
        return GameVisibility::HIDDEN_INFORMATION;
    }

    protected const DEFAULT_TURN_TIME_SECONDS = 30;

    abstract protected function getReporter(): GameReporterContract;

    abstract public function getArbiter(): GameArbiterContract;

    public function getTimelimit(): int
    {
        return static::DEFAULT_TURN_TIME_SECONDS;
    }

    // GameReporterContract delegation

    public function getPublicStatus(object $gameState): array
    {
        return $this->getReporter()->getPublicStatus($gameState);
    }

    public function describeStateChanges(Game $game, Action $action, object $gameState): array
    {
        return $this->getReporter()->describeStateChanges($game, $action, $gameState);
    }

    public function formatActionSummary(Action $action): string
    {
        return $this->getReporter()->formatActionSummary($action);
    }

    public function getFinishDetails(Game $game, GameOutcome $outcome, object $gameState): array
    {
        return $this->getReporter()->getFinishDetails($game, $outcome, $gameState);
    }

    public function analyzeOutcome(Game $game, GameOutcome $outcome, object $gameState): array
    {
        return $this->getReporter()->analyzeOutcome($game, $outcome, $gameState);
    }

    // GameArbiterContract delegation

    /**
     * Standard 52-card deck representation.
     * Format: [suit][rank] (e.g., 'H2' = 2 of Hearts, 'SQ' = Queen of Spades)
     *
     * Suits: H (Hearts), D (Diamonds), C (Clubs), S (Spades)
     * Ranks: A, 2-10, J, Q, K
     */
    protected const STANDARD_DECK = [
        'HA', 'H2', 'H3', 'H4', 'H5', 'H6', 'H7', 'H8', 'H9', 'H10', 'HJ', 'HQ', 'HK',
        'DA', 'D2', 'D3', 'D4', 'D5', 'D6', 'D7', 'D8', 'D9', 'D10', 'DJ', 'DQ', 'DK',
        'CA', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'C9', 'C10', 'CJ', 'CQ', 'CK',
        'SA', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'S10', 'SJ', 'SQ', 'SK',
    ];

    /**
     * Create a new shuffled deck.
     *
     * @return array<string> Shuffled array of card codes
     */
    protected function createShuffledDeck(): array
    {
        $deck = self::STANDARD_DECK;
        shuffle($deck);

        return $deck;
    }

    /**
     * Get the suit of a card.
     *
     * @param  string  $card  Card code (e.g., 'H2', 'SQ')
     * @return string The suit letter (H, D, C, or S)
     */
    protected function getSuit(string $card): string
    {
        return $card[0];
    }

    /**
     * Get the rank of a card.
     *
     * @param  string  $card  Card code (e.g., 'H2', 'SQ')
     * @return string The rank (A, 2-10, J, Q, K)
     */
    protected function getRank(string $card): string
    {
        return substr($card, 1);
    }

    /**
     * Get the numeric value of a card rank.
     *
     * Aces are 1, face cards are J=11, Q=12, K=13.
     * Games can override this for different ace values.
     *
     * @param  string  $card  Card code
     * @return int Numeric value of the rank
     */
    protected function getRankValue(string $card): int
    {
        $rank = $this->getRank($card);

        return match ($rank) {
            'A' => 1,
            'J' => 11,
            'Q' => 12,
            'K' => 13,
            default => (int) $rank,
        };
    }

    /**
     * Check if two cards are of the same suit.
     *
     * @param  string  $card1  First card code
     * @param  string  $card2  Second card code
     * @return bool True if cards have the same suit
     */
    protected function isSameSuit(string $card1, string $card2): bool
    {
        return $this->getSuit($card1) === $this->getSuit($card2);
    }

    /**
     * Deal cards from a deck to multiple hands.
     *
     * @param  array<string>  $deck  The deck to deal from
     * @param  int  $numHands  Number of hands to deal
     * @param  int  $cardsPerHand  Cards per hand
     * @return array<int, array<string>> Array of hands
     */
    protected function dealCards(array $deck, int $numHands, int $cardsPerHand): array
    {
        $hands = array_fill(0, $numHands, []);

        for ($i = 0; $i < $cardsPerHand; $i++) {
            for ($j = 0; $j < $numHands; $j++) {
                $hands[$j][] = array_shift($deck);
            }
        }

        return $hands;
    }

    /**
     * Returns the base rules for card games.
     */
    public static function getRules(): array
    {
        $rules = parent::getRules();
        $rules['description'] = 'Base description for a card game.';

        return $rules;
    }
}
