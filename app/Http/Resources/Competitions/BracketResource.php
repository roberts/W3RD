<?php

namespace App\Http\Resources\Competitions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BracketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tournament_id' => $this->resource['tournament_id'] ?? null,
            'format' => $this->resource['format'] ?? null,
            'rounds' => $this->resource['rounds'] ?? [],
            'current_round' => $this->resource['current_round'] ?? null,
            'matches' => $this->resource['matches'] ?? [],
            'participants' => $this->resource['participants'] ?? [],
            'updated_at' => $this->resource['updated_at'] ?? null,
        ];
    }
}
