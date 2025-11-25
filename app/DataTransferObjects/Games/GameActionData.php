<?php

namespace App\DataTransferObjects\Games;

use App\Models\Games\Action;
use Spatie\LaravelData\Data;

class GameActionData extends Data
{
    public function __construct(
        public int $id,
        public string $action_type,
        /** @var array<string, mixed> */
        public array $action_details,
        public string $player_ulid,
        public ?string $username,
        public int $turn_number,
        /** @var array<string, mixed>|null */
        public ?array $resulting_state,
        public string $created_at,
    ) {}

    public static function fromModel(Action $action): self
    {
        return new self(
            id: $action->id,
            action_type: $action->action_type->value,
            action_details: $action->action_details ?? [],
            player_ulid: $action->player->ulid,
            username: $action->player->user->username,
            turn_number: $action->turn_number,
            resulting_state: $action->resulting_state,
            created_at: $action->created_at->toIso8601String(),
        );
    }
}
