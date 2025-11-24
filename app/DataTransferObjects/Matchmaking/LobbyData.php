<?php

namespace App\DataTransferObjects\Matchmaking;

use App\Models\Matchmaking\Lobby;
use App\Models\Matchmaking\LobbyPlayer;
use Spatie\LaravelData\Data;

class LobbyData extends Data
{
    public function __construct(
        public string $ulid,
        public string $game_title,
        public ?string $game_mode,
        public array $host,
        public array $players,
        public string $status,
        public bool $is_public,
        public int $min_players,
        public ?string $scheduled_at,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(Lobby $lobby): self
    {
        return new self(
            ulid: $lobby->ulid,
            game_title: $lobby->title_slug->value,
            game_mode: $lobby->mode?->slug,
            host: [
                'id' => $lobby->host_id,
                'username' => optional($lobby->host)->username,
            ],
            players: $lobby->players->map(function (LobbyPlayer $player) {
                return [
                    'id' => $player->user_id,
                    'status' => $player->status->value,
                    'client_id' => $player->client_id,
                ];
            })->toArray(),
            status: $lobby->status->value,
            is_public: $lobby->is_public,
            min_players: $lobby->min_players,
            scheduled_at: optional($lobby->scheduled_at)?->toIso8601String(),
            created_at: $lobby->created_at->toIso8601String(),
            updated_at: $lobby->updated_at->toIso8601String(),
        );
    }
}
