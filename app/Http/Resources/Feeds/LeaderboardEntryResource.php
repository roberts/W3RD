<?php

namespace App\Http\Resources\Feeds;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaderboardEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->resource['rank'] ?? null,
            'username' => $this->resource['username'] ?? null,
            'display_name' => $this->resource['display_name'] ?? null,
            'avatar_url' => $this->resource['avatar_url'] ?? null,
            'elo_rating' => $this->resource['elo_rating'] ?? null,
            'games_played' => $this->resource['games_played'] ?? null,
            'wins' => $this->resource['wins'] ?? null,
            'losses' => $this->resource['losses'] ?? null,
            'win_rate' => $this->resource['win_rate'] ?? null,
            'points' => $this->resource['points'] ?? null,
        ];
    }
}
