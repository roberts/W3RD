<?php

namespace App\DataTransferObjects\Matchmaking;

use App\Models\Matchmaking\Proposal;
use Spatie\LaravelData\Data;

class ProposalData extends Data
{
    public function __construct(
        public string $ulid,
        public int $requesting_user_id,
        public int $opponent_user_id,
        public string $type,
        public ?string $title_slug,
        public ?int $mode_id,
        public ?array $game_settings,
        public string $status,
        public ?string $responded_at,
        public ?string $expires_at,
        public ?string $original_game_ulid,
        public ?string $game_ulid,
    ) {}

    public static function fromModel(Proposal $proposal): self
    {
        return new self(
            ulid: $proposal->ulid,
            requesting_user_id: $proposal->requesting_user_id,
            opponent_user_id: $proposal->opponent_user_id,
            type: $proposal->type,
            title_slug: $proposal->title_slug,
            mode_id: $proposal->mode_id,
            game_settings: $proposal->game_settings,
            status: $proposal->status,
            responded_at: optional($proposal->responded_at)?->toIso8601String(),
            expires_at: optional($proposal->expires_at)?->toIso8601String(),
            original_game_ulid: optional($proposal->originalGame)->ulid,
            game_ulid: optional($proposal->game)->ulid,
        );
    }
}
