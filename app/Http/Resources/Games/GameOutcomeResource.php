<?php

namespace App\Http\Resources\Games;

use App\Models\Games\Game;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Game outcome resource for completed games.
 *
 * @mixin Game
 */
class GameOutcomeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $winner = $this->winner;

        return [
            'game_ulid' => $this->ulid,
            'status' => $this->status->value,
            'outcome_type' => $this->outcome_type?->value,
            'winner' => $winner ? [
                'ulid' => $winner->ulid,
                'username' => $winner->user->username,
            ] : null,
            'is_draw' => $this->winner_id === null && $this->outcome_type?->value === 'draw',
            'completed_at' => $this->completed_at?->toIso8601String(),
            'duration_seconds' => $this->duration_seconds,
            'final_scores' => $this->final_scores ?? [],
            'xp_awarded' => $this->xp_awarded ?? [],
            'rewards' => $this->rewards ?? [],
        ];
    }
}
