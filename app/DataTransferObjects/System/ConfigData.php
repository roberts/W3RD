<?php

namespace App\DataTransferObjects\System;

use Spatie\LaravelData\Data;

class ConfigData extends Data
{
    public function __construct(
        public string $api_version,
        public string $platform_name,
        /** @var array<string, mixed> */
        public array $features,
        /** @var array<int, string> */
        public array $supported_games,
        /** @var array<string, mixed> */
        public array $limits,
        /** @var array<string, mixed> */
        public array $maintenance,
    ) {}
}
