<?php

namespace App\DataTransferObjects\Library;

use Spatie\LaravelData\Data;

class GameEntityData extends Data
{
    public function __construct(
        public string $type,
        /** @var array<int, mixed> */
        public array $entities,
        public bool $cacheable,
        public int $cache_duration_seconds,
    ) {}
}
