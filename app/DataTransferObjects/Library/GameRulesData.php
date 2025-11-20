<?php

namespace App\DataTransferObjects\Library;

use Spatie\LaravelData\Data;

class GameRulesData extends Data
{
    public function __construct(
        public string $game,
        public string $objective,
        public array $setup,
        public array $gameplay,
        public array $scoring,
        public array $modes,
    ) {}
}
