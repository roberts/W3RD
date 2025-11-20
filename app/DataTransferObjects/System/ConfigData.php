<?php

namespace App\DataTransferObjects\System;

use Spatie\LaravelData\Data;

class ConfigData extends Data
{
    public function __construct(
        public string $api_version,
        public string $platform_name,
        public array $features,
        public array $supported_games,
        public array $limits,
        public array $maintenance,
    ) {}
}
