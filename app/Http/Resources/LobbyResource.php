<?php

namespace App\Http\Resources;

use App\Models\Game\Lobby;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lobby
 */
class LobbyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'game_title' => $this->game_title->value,
            'game_mode' => $this->game_mode,
            'host' => UserResource::make($this->host),
            'min_players' => $this->min_players,
            'current_players' => $this->whenLoaded('players', fn() => $this->acceptedPlayers()->count()),
            'is_public' => $this->is_public,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'status' => $this->status->value,
            'players' => $this->whenLoaded('players', fn() => LobbyPlayerResource::collection($this->players)),
        ];
    }
}
