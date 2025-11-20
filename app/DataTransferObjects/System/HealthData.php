<?php

namespace App\DataTransferObjects\System;

use Spatie\LaravelData\Data;

class HealthData extends Data
{
    public function __construct(
        public string $status,
        public string $timestamp,
        public array $services,
    ) {}
}
