<?php

namespace App\DataTransferObjects\Feeds;

use Spatie\LaravelData\Data;

class FeedEventData extends Data
{
    public function __construct(
        public string $type,
        public string $timestamp,
        /** @var array<string, mixed> */
        public array $data,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function create(string $type, array $data): self
    {
        return new self(
            type: $type,
            timestamp: now()->toIso8601String(),
            data: $data,
        );
    }
}
