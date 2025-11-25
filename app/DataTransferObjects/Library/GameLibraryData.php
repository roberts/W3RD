<?php

namespace App\DataTransferObjects\Library;

use Spatie\LaravelData\Data;

class GameLibraryData extends Data
{
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        public int $min_players,
        public int $max_players,
        public string $pacing,
        public string $complexity,
        /** @var array<int, string> */
        public array $categories,
    ) {}
}
