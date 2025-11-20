<?php

declare(strict_types=1);

namespace App\GameEngine\Actions;

use App\GameEngine\Interfaces\GameActionContract;

class ClaimRemainingTricks implements GameActionContract
{
    public function __construct() {}

    public function getType(): string
    {
        return 'claim_remaining_tricks';
    }

    public function toArray(): array
    {
        return [];
    }
}
