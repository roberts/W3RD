<?php

namespace App\DataTransferObjects\Competitions;

use App\Models\Competitions\Tournament;
use Spatie\LaravelData\Data;

class CompetitionData extends Data
{
    public function __construct(
        public string $ulid,
        public string $name,
        public string $game_title,
        public string $format,
        public string $status,
        public int $max_participants,
        public int $current_participants,
        public int $buy_in_amount,
        public string $buy_in_currency,
        public int $prize_pool,
        public ?array $rules,
        public ?string $starts_at,
        public ?string $ends_at,
        public string $created_at,
    ) {}

    public static function fromModel(Tournament $tournament): self
    {
        return new self(
            ulid: $tournament->ulid,
            name: $tournament->name,
            game_title: $tournament->game_title,
            format: $tournament->format,
            status: $tournament->status,
            max_participants: $tournament->max_participants,
            current_participants: $tournament->users()->count(),
            buy_in_amount: $tournament->buy_in_amount ?? 0,
            buy_in_currency: $tournament->buy_in_currency ?? 'chips',
            prize_pool: $tournament->prize_pool ?? 0,
            rules: $tournament->rules,
            starts_at: $tournament->starts_at?->toIso8601String(),
            ends_at: $tournament->ends_at?->toIso8601String(),
            created_at: $tournament->created_at->toIso8601String(),
        );
    }
}
