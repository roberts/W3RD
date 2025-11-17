<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Game\LobbyPlayer
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
            'username' => $this->user->username, // @phpstan-ignore-line
            'name' => $this->user->name, // @phpstan-ignore-line
            'avatar' => $this->user->avatar?->image?->url, // @phpstan-ignore-line
            'status' => $this->status,
            'invited_at' => $this->invited_at?->toIso8601String(), // @phpstan-ignore-line
            'joined_at' => $this->joined_at?->toIso8601String(), // @phpstan-ignore-line
        ];
    }
}
