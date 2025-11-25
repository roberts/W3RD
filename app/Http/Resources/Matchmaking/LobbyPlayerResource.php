<?php

namespace App\Http\Resources\Matchmaking;

use App\Models\Matchmaking\LobbyPlayer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LobbyPlayer
 */
class LobbyPlayerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'username' => $this->user->username,
            'name' => $this->user->name,
            'avatar' => $this->user->avatar?->image?->url,
            'status' => $this->status,
            'invited_at' => $this->invited_at?->toIso8601String(), // @phpstan-ignore-line
            'joined_at' => $this->joined_at?->toIso8601String(), // @phpstan-ignore-line
        ];
    }
}
