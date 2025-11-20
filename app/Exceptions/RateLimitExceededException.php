<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when a rate limit is exceeded.
 *
 * Used for standard time-window rate limiting (e.g., X requests per Y seconds).
 * Returns HTTP 429 with Retry-After header.
 *
 * For game-specific cooldowns, use CooldownActiveException instead.
 * For concurrency conflicts (already in game/lobby), use PlayerBusyException.
 */
class RateLimitExceededException extends Exception
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        public readonly ?int $retryAfter = null,
        public readonly ?int $limit = null,
        public readonly ?int $window = null,
        public readonly ?array $context = []
    ) {
        parent::__construct($message);
    }
}
