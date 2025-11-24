<?php

namespace App\DataTransferObjects\Games;

use App\Models\Games\Game;
use Spatie\LaravelData\Data;

class GameData extends Data
{
    public function __construct(
        public string $ulid,
        public string $game_title,
        public ?string $game_mode,
        public string $status,
        /** @var array<int, mixed> */
        public array $players,
        public ?int $winner_id,
        public ?string $outcome_type,
        /** @var array<string, mixed>|null */
        public ?array $game_state,
        /** @var array<string, int>|null */
        public ?array $final_scores,
        /** @var array<string, int>|null */
        public ?array $xp_awarded,
        /** @var array<string, mixed>|null */
        public ?array $rewards,
        public ?string $started_at,
        public ?string $completed_at,
        public ?int $duration_seconds,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(Game $game): self
    {
        return new self(
            ulid: $game->ulid,
            game_title: $game->title_slug->value,
            game_mode: $game->mode->slug,
            status: $game->status->value,
            players: $game->players->map(fn ($player) => [
                'ulid' => $player->ulid,
                'user_id' => $player->user_id,
                'username' => $player->user->username,
                'position' => $player->position,
            ])->toArray(),
            winner_id: $game->winner_id,
            outcome_type: $game->outcome_type?->value,
            game_state: $game->game_state,
            final_scores: $game->final_scores,
            xp_awarded: $game->xp_awarded,
            rewards: $game->rewards,
            started_at: $game->started_at?->toIso8601String(),
            completed_at: $game->completed_at?->toIso8601String(),
            duration_seconds: $game->duration_seconds,
            created_at: $game->created_at->toIso8601String(),
            updated_at: $game->updated_at->toIso8601String(),
        );
    }
}
