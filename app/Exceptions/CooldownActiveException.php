<?php

namespace App\Exceptions;

/**
 * Exception thrown when a game-specific cooldown is active.
 *
 * Used for penalty-based cooldowns such as:
 * - Post-game cooldowns
 * - Dodge penalties
 * - Rage quit penalties
 * - Fair play timeouts
 *
 * Returns HTTP 429 with Retry-After header and cooldown reason.
 * Extends RateLimitExceededException but semantically represents
 * a penalty rather than a pure rate limit.
 */
class CooldownActiveException extends RateLimitExceededException
{
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        string $message = 'Cooldown is active',
        public readonly ?string $reason = null,
        ?int $retryAfter = null,
        ?array $context = []
    ) {
        parent::__construct($message, $retryAfter, null, null, $context);
    }
}
