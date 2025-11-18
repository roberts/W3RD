<?php

namespace App\DataTransferObjects\Game;

use Illuminate\Http\JsonResponse;

class TimeoutResult
{
    public function __construct(
        public readonly bool $hasTimedOut,
        public readonly ?JsonResponse $errorResponse = null,
    ) {}

    public static function noTimeout(): self
    {
        return new self(hasTimedOut: false);
    }

    public static function timeout(JsonResponse $errorResponse): self
    {
        return new self(
            hasTimedOut: true,
            errorResponse: $errorResponse
        );
    }
}
