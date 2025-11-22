<?php

declare(strict_types=1);

namespace App\Matchmaking\Results;

use App\Models\Matchmaking\Lobby;

/**
 * Result object for lobby operations.
 */
readonly class LobbyOperationResult
{
    private function __construct(
        public bool $success,
        public ?Lobby $lobby,
        public ?string $errorMessage,
        public ?string $message = null,
    ) {}

    public static function success(Lobby $lobby, ?string $message = null): self
    {
        return new self(
            success: true,
            lobby: $lobby,
            errorMessage: null,
            message: $message,
        );
    }

    public static function failed(string $errorMessage): self
    {
        return new self(
            success: false,
            lobby: null,
            errorMessage: $errorMessage,
            message: null,
        );
    }

    public function toArray(): array
    {
        if (! $this->success) {
            return [
                'success' => false,
                'error' => $this->errorMessage,
            ];
        }

        return [
            'success' => true,
            'lobby' => [
                'ulid' => $this->lobby->ulid,
                'game_title' => $this->lobby->game_title->value,
                'game_mode' => $this->lobby->game_mode,
                'status' => $this->lobby->status->value,
                'is_public' => $this->lobby->is_public,
                'host_id' => $this->lobby->host_id,
            ],
        ];
    }
}
