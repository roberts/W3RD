<?php

declare(strict_types=1);

namespace App\Matchmaking\Results;

use App\Models\MatchmakingSignal;

/**
 * Result object for quickplay/matchmaking operations.
 */
readonly class QuickplayResult
{
    private function __construct(
        public bool $success,
        public ?MatchmakingSignal $signal,
        public ?string $errorMessage,
        public ?int $cooldownRemaining,
        public array $context,
    ) {}

    public static function success(?MatchmakingSignal $signal, array $context = []): self
    {
        return new self(
            success: true,
            signal: $signal,
            errorMessage: null,
            cooldownRemaining: null,
            context: $context,
        );
    }

    public static function cooldownActive(int $remainingSeconds, string $message = 'Please wait before joining another game'): self
    {
        return new self(
            success: false,
            signal: null,
            errorMessage: $message,
            cooldownRemaining: $remainingSeconds,
            context: ['cooldown_remaining' => $remainingSeconds],
        );
    }

    public static function failed(string $message, array $context = []): self
    {
        return new self(
            success: false,
            signal: null,
            errorMessage: $message,
            cooldownRemaining: null,
            context: $context,
        );
    }

    public function toArray(): array
    {
        if (! $this->success) {
            return [
                'success' => false,
                'error' => $this->errorMessage,
                'cooldown_remaining' => $this->cooldownRemaining,
                'context' => $this->context,
            ];
        }

        $response = [
            'success' => true,
            'context' => $this->context,
        ];

        if ($this->signal) {
            $response['signal'] = [
                'ulid' => $this->signal->ulid,
                'game_preference' => $this->signal->game_preference,
                'preferences' => $this->signal->preferences,
                'status' => $this->signal->status,
                'expires_at' => $this->signal->expires_at?->toIso8601String(),
            ];
        }

        return $response;
    }
}
