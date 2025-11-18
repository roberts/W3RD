<?php

declare(strict_types=1);

namespace App\Games\Hearts\Actions;

use App\Interfaces\GameActionContract;

/**
 * Play card action for Hearts.
 *
 * During trick-taking, players play one card from their hand.
 *
 * Rules:
 * - Must follow suit if possible
 * - Can't lead hearts until hearts are broken (unless only hearts remain)
 * - Can't play Queen of Spades or hearts on first trick
 * - 2 of Clubs must lead the first trick
 */
final class PlayCard implements GameActionContract
{
    /**
     * @param  string  $card  Card identifier (e.g., 'H2', 'SQ', 'DK', 'CA')
     */
    public function __construct(
        public readonly string $card,
    ) {}

    /**
     * Get action type identifier.
     */
    public function getType(): string
    {
        return 'play_card';
    }

    /**
     * Convert action to array for storage.
     */
    public function toArray(): array
    {
        return [
            'card' => $this->card,
        ];
    }

    /**
     * Get the suit of the card.
     */
    public function getSuit(): string
    {
        return $this->card[0];
    }

    /**
     * Get the rank of the card.
     */
    public function getRank(): string
    {
        return substr($this->card, 1);
    }

    /**
     * Check if this is a heart.
     */
    public function isHeart(): bool
    {
        return $this->getSuit() === 'H';
    }

    /**
     * Check if this is the Queen of Spades.
     */
    public function isQueenOfSpades(): bool
    {
        return $this->card === 'SQ';
    }

    /**
     * Check if this is the 2 of Clubs.
     */
    public function isTwoOfClubs(): bool
    {
        return $this->card === 'C2';
    }
}
