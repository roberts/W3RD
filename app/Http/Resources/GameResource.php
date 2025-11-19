<?php

namespace App\Http\Resources;

use App\Models\Game\Game;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Game
 */
class GameResource extends JsonResource
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
            'game_title' => $this->mode->title_slug->value ?? null,
            'status' => $this->status->value,
            'turn_number' => $this->turn_number,
            'winner_id' => $this->winner_id,
            'players' => PlayerResource::collection($this->players),
            'game_state' => $this->game_state,
            'completed_at' => $this->when($this->completed_at !== null, $this->completed_at),
            'outcome_type' => $this->when($this->outcome_type !== null, $this->outcome_type),
            'outcome_details' => $this->when($this->outcome_details !== null, $this->outcome_details),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
