<?php

namespace App\DataTransferObjects\Account;

use Spatie\LaravelData\Data;

class RecordsData extends Data
{
    public function __construct(
        public int $total_games,
        public int $wins,
        public int $losses,
        public int $draws,
        public float $win_rate,
        public int $total_points,
        public array $elo_ratings,
        public ?int $global_rank,
    ) {}
}
