<?php

namespace App\DataTransferObjects\Floor;

use App\Models\MatchmakingSignal;
use Spatie\LaravelData\Data;

class SignalData extends Data
{
    public function __construct(
        public string $ulid,
        public int $user_id,
        public string $game_preference,
        public ?string $game_mode,
        public ?int $skill_rating,
        public string $status,
        public array $preferences,
        public ?string $expires_at,
        public string $created_at,
    ) {}

    public static function fromModel(MatchmakingSignal $signal): self
    {
        return new self(
            ulid: $signal->ulid,
            user_id: $signal->user_id,
            game_preference: $signal->game_preference,
            game_mode: $signal->preferences['game_mode'] ?? null,
            skill_rating: $signal->skill_rating,
            status: $signal->status,
            preferences: $signal->preferences ?? [],
            expires_at: optional($signal->expires_at)?->toIso8601String(),
            created_at: $signal->created_at->toIso8601String(),
        );
    }
}
