<?php

declare(strict_types=1);

namespace App\Games\Hearts\Actions;

use App\GameEngine\Interfaces\GameActionContract;

/**
 * Deal Cards Action
 *
 * Deals cards to all players.
 */
final class DealCards implements GameActionContract
{
    public function __construct() {}

    /**
     * Get action type identifier.
     */
    public function getType(): string
    {
        return 'deal_cards';
    }

    /**
     * Convert action to array for storage.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
        ];
    }
}
