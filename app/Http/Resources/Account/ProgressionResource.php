<?php

namespace App\Http\Resources\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgressionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'level' => $this->resource['level'] ?? 1,
            'experience_points' => $this->resource['experience_points'] ?? 0,
            'next_level_xp' => $this->resource['next_level_xp'] ?? null,
            'progress_to_next_level' => $this->resource['progress_to_next_level'] ?? 0,
            'titles' => $this->resource['titles'] ?? [],
            'active_title' => $this->resource['active_title'] ?? null,
            'badges' => $this->resource['badges'] ?? [],
            'achievements' => $this->resource['achievements'] ?? [],
            'milestones' => $this->resource['milestones'] ?? [],
        ];
    }
}
