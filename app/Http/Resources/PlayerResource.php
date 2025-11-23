<?php

namespace App\Http\Resources;

use App\Models\Games\Player;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Player
 */
class PlayerResource extends JsonResource
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
            'position_id' => $this->position_id,
            'color' => $this->color,
            'avatar' => $this->user->avatar?->image?->url, // @phpstan-ignore-line
        ];
    }
}
