<?php

namespace App\DataTransferObjects\Games;

use Spatie\LaravelData\Data;

class GameOutcomeData extends Data
{
    public function __construct(
        public string $game_ulid,
        public string $status,
        public ?string $outcome_type,
        /** @var array<string, mixed>|null */
        public ?array $winner,
        public bool $is_draw,
        public ?string $completed_at,
        public ?int $duration_seconds,
        /** @var array<string, int> */
        public array $final_scores,
        /** @var array<string, int> */
        public array $xp_awarded,
        /** @var array<string, mixed> */
        public array $rewards,
    ) {}

    /**
     * @param  array<string, mixed>|null  $winner
     * @param  array<string, int>  $finalScores
     * @param  array<string, int>  $xpAwarded
     * @param  array<string, mixed>  $rewards
     */
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
