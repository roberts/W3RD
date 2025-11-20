<?php

namespace App\DataTransferObjects\Account;

use Spatie\LaravelData\Data;

class ProgressionData extends Data
{
    public function __construct(
        public array $games,
        public int $total_xp,
        public float $average_level,
        public ?array $battle_pass,
    ) {}
}
