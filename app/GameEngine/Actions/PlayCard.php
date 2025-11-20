<?php

declare(strict_types=1);

namespace App\GameEngine\Actions;

use App\GameEngine\Interfaces\GameActionContract;

class PlayCard implements GameActionContract
{
    public function __construct(
        public readonly string $card,
    ) {}

    public function getType(): string
    {
        return 'play_card';
    }

    public function toArray(): array
    {
        return [
            'card' => $this->card,
        ];
    }
}
