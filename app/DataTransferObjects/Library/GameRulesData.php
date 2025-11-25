<?php

namespace App\DataTransferObjects\Library;

use Spatie\LaravelData\Data;

class GameRulesData extends Data
{
    public function __construct(
        public string $game,
        public string $objective,
        /** @var array<string, mixed> */
        public array $setup,
        /** @var array<string, mixed> */
        public array $gameplay,
        /** @var array<string, mixed> */
        public array $scoring,
        /** @var array<string, mixed> */
        public array $modes,
    ) {}
}
