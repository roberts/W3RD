<?php

declare(strict_types=1);

namespace App\Games\Hearts\Actions;

use App\Interfaces\GameActionContract;

/**
 * Pass cards action for Hearts.
 *
 * At the start of each round (except "hold" rounds), players pass 3 cards
 * to another player based on the round number:
 * - Round 1, 5, 9...: Pass to the left
 * - Round 2, 6, 10...: Pass to the right
 * - Round 3, 7, 11...: Pass across
 * - Round 4, 8, 12...: Hold (no passing)
 */
final class PassCards implements GameActionContract
{
    /**
     * @param  array<string>  $cards  Array of 3 card identifiers (e.g., ['H2', 'S5', 'DK'])
     */
    public function __construct(
        public readonly array $cards,
    ) {}

    /**
     * Get action type identifier.
     */
    public function getType(): string
    {
        return 'pass_cards';
    }

    /**
     * Convert action to array for storage.
     */
    public function toArray(): array
    {
        return [
            'cards' => $this->cards,
        ];
    }
}
