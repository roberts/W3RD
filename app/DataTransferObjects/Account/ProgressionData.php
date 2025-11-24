<?php

namespace App\DataTransferObjects\Account;

use Spatie\LaravelData\Data;

class ProgressionData extends Data
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $games,
        public int $total_xp,
        public float $average_level,
        /** @var array<string, mixed>|null */
        public ?array $battle_pass,
    ) {}
}
