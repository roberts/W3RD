<?php

declare(strict_types=1);

namespace App\Games\Hearts\Actions;

use App\Interfaces\GameActionContract;

/**
 * Claim Remaining Tricks Action
 *
 * Allows a player to claim all remaining tricks when they hold all winning cards.
 */
final class ClaimRemainingTricks implements GameActionContract
{
    public function __construct() {}

    /**
     * Get action type identifier.
     */
    public function getType(): string
    {
        return 'claim_remaining_tricks';
    }

    /**
     * Convert action to array for storage.
     */
    public function toArray(): array
    {
        return [];
    }
}
