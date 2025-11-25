<?php

namespace App\DataTransferObjects\Account;

use Spatie\LaravelData\Data;

class AlertData extends Data
{
    public function __construct(
        public string $ulid,
        public string $type,
        public string $message,
        /** @var array<string, mixed> */
        public array $data,
        public ?string $read_at,
        public string $created_at,
    ) {}
}
