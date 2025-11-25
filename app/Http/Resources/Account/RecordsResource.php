<?php

namespace App\Http\Resources\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecordsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_games' => $this->resource['total_games'] ?? 0,
            'games_won' => $this->resource['games_won'] ?? 0,
            'games_lost' => $this->resource['games_lost'] ?? 0,
            'games_drawn' => $this->resource['games_drawn'] ?? 0,
            'win_rate' => $this->resource['win_rate'] ?? 0,
            'current_streak' => $this->resource['current_streak'] ?? 0,
            'longest_win_streak' => $this->resource['longest_win_streak'] ?? 0,
            'total_points' => $this->resource['total_points'] ?? 0,
            'elo_ratings' => $this->resource['elo_ratings'] ?? [],
            'global_rank' => $this->resource['global_rank'] ?? null,
            'games_by_title' => $this->resource['games_by_title'] ?? [],
            'favorite_game' => $this->resource['favorite_game'] ?? null,
        ];
    }
}
