<?php

declare(strict_types=1);

namespace App\GameEngine\Actions;

use App\GameEngine\Interfaces\GameActionContract;

class PassCards implements GameActionContract
{
    public function __construct(
        /** @var array<int, string> */
        public readonly array $cards,
    ) {}

    public function getType(): string
    {
        return 'pass_cards';
    }

    public function toArray(): array
    {
        return [
            'cards' => $this->cards,
        ];
    }
}
