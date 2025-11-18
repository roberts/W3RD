<?php

namespace App\DataTransferObjects\Quickplay;

class QueueJoinResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $gameTitle = null,
        public readonly ?string $gameMode = null,
        public readonly ?int $cooldownRemaining = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function success(string $gameTitle, string $gameMode): self
    {
        return new self(
            success: true,
            gameTitle: $gameTitle,
            gameMode: $gameMode
        );
    }

    public static function cooldown(int $cooldownRemaining): self
    {
        return new self(
            success: false,
            cooldownRemaining: $cooldownRemaining,
            errorMessage: 'You are on a matchmaking cooldown'
        );
    }
}
