<?php

declare(strict_types=1);

namespace App\Matchmaking\Results;

use App\Models\Matchmaking\QueueSlot;

/**
 * Result object for queue/matchmaking operations.
 */
readonly class QueueResult
{
    private function __construct(
        public bool $success,
        public ?QueueSlot $slot,
        public ?string $errorMessage,
        public ?int $cooldownRemaining,
        public array $context,
    ) {}

    public static function success(?QueueSlot $slot, array $context = []): self
    {
        return new self(
            success: true,
            slot: $slot,
            errorMessage: null,
            cooldownRemaining: null,
            context: $context,
        );
    }

    public static function cooldownActive(int $remainingSeconds, string $message = 'Please wait before joining another game'): self
    {
        return new self(
            success: false,
            slot: null,
            errorMessage: $message,
            cooldownRemaining: $remainingSeconds,
            context: ['cooldown_remaining' => $remainingSeconds],
        );
    }

    public static function failed(string $message, array $context = []): self
    {
        return new self(
            success: false,
            slot: null,
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

        if ($this->slot) {
            $response['slot'] = [
                'ulid' => $this->slot->ulid,
                'title_slug' => $this->slot->title_slug,
                'mode_id' => $this->slot->mode_id,
                'preferences' => $this->slot->preferences,
                'status' => $this->slot->status,
                'expires_at' => $this->slot->expires_at?->toIso8601String(),
            ];
        }

        return $response;
    }
}
