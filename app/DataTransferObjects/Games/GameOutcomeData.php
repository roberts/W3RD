<?php

namespace App\DataTransferObjects\Games;

use Spatie\LaravelData\Data;

class GameOutcomeData extends Data
{
    public function __construct(
        public string $game_ulid,
        public string $status,
        public ?string $outcome_type,
        public ?array $winner,
        public bool $is_draw,
        public ?string $completed_at,
        public ?int $duration_seconds,
        public array $final_scores,
        public array $xp_awarded,
        public array $rewards,
    ) {}

    public static function create(
        string $gameUlid,
        string $status,
        ?string $outcomeType,
        ?array $winner,
        bool $isDraw,
        ?string $completedAt,
        ?int $durationSeconds,
        array $finalScores = [],
        array $xpAwarded = [],
        array $rewards = []
    ): self {
        return new self(
            game_ulid: $gameUlid,
            status: $status,
            outcome_type: $outcomeType,
            winner: $winner,
            is_draw: $isDraw,
            completed_at: $completedAt,
            duration_seconds: $durationSeconds,
            final_scores: $finalScores,
            xp_awarded: $xpAwarded,
            rewards: $rewards,
        );
    }
}
