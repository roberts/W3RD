<?php

namespace App\Games;

use Spatie\LaravelData\Data;

abstract class BasePlayerState extends Data
{
    public function __construct(
        public readonly string $ulid,
    ) {
    }
}
