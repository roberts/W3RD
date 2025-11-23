<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Game timer information resource.
 */
class GameTimerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'current_player' => [
                'ulid' => $this->resource['currentPlayer']?->ulid,
                'user_id' => $this->resource['currentPlayer']?->user_id,
                'username' => $this->resource['currentPlayer']?->user?->username,
            ],
            'is_your_turn' => $this->resource['isYourTurn'],
            'phase' => $this->resource['phase'] ?? null,
            'timer' => $this->resource['timerInfo'],
        ];
    }
}
