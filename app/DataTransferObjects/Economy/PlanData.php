<?php

namespace App\DataTransferObjects\Economy;

use Spatie\LaravelData\Data;

class PlanData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public int $price,
        public string $currency,
        public string $interval,
        /** @var array<string, mixed> */
        public array $features,
    ) {}
}
