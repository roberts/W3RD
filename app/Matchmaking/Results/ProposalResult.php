<?php

declare(strict_types=1);

namespace App\Matchmaking\Results;

use App\Models\Game\Game;
use App\Models\Matchmaking\Proposal;

/**
 * Result object for proposal operations (rematch/challenge).
 */
readonly class ProposalResult
{
    private function __construct(
        public bool $success,
        public ?Proposal $proposal,
        public ?Game $game,
        public ?string $errorMessage,
        public array $context,
    ) {}

    public static function success(?Proposal $proposal, ?Game $game = null, array $context = []): self
    {
        return new self(
            success: true,
            proposal: $proposal,
            game: $game,
            errorMessage: null,
            context: $context,
        );
    }

    public static function failed(string $message, array $context = []): self
    {
        return new self(
            success: false,
            proposal: null,
            game: null,
            errorMessage: $message,
            context: $context,
        );
    }

    public function toArray(): array
    {
        if (! $this->success) {
            return [
                'success' => false,
                'error' => $this->errorMessage,
                'context' => $this->context,
            ];
        }

        $response = [
            'success' => true,
            'context' => $this->context,
        ];

        if ($this->proposal) {
            $response['proposal'] = [
                'ulid' => $this->proposal->ulid,
                'type' => $this->proposal->type->value,
                'status' => $this->proposal->status->value,
                'requesting_user_id' => $this->proposal->requesting_user_id,
                'opponent_user_id' => $this->proposal->opponent_user_id,
                'title_slug' => $this->proposal->title_slug,
                'expires_at' => $this->proposal->expires_at?->toIso8601String(),
            ];
        }

        if ($this->game) {
            $response['game'] = [
                'ulid' => $this->game->ulid,
                'status' => $this->game->status->value,
            ];
        }

        return $response;
    }
}
