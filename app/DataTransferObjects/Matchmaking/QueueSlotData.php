<?php

namespace App\DataTransferObjects\Matchmaking;

use App\Models\Matchmaking\QueueSlot;
use Spatie\LaravelData\Data;

class QueueSlotData extends Data
{
    public function __construct(
        public string $ulid,
        public int $user_id,
        public string $title_slug,
        public int $mode_id,
        public ?string $game_mode,
        public ?int $skill_rating,
        public string $status,
        public array $preferences,
        public ?string $expires_at,
        public string $created_at,
    ) {}

    public static function fromModel(QueueSlot $slot): self
    {
        return new self(
            ulid: $slot->ulid,
            user_id: $slot->user_id,
            title_slug: $slot->title_slug,
            mode_id: $slot->mode_id,
            game_mode: $slot->preferences['game_mode'] ?? null,
            skill_rating: $slot->skill_rating,
            status: $slot->status,
            preferences: $slot->preferences ?? [],
            expires_at: optional($slot->expires_at)?->toIso8601String(),
            created_at: $slot->created_at->toIso8601String(),
        );
    }
}
