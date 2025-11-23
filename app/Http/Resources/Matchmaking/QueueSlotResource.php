<?php

namespace App\Http\Resources\Account;

use App\Models\Matchmaking\QueueSlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QueueSlot
 */
class QueueSlotResource extends JsonResource
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
            'mode_id' => $this->mode_id,
            'mode_name' => $this->mode->name ?? null,
            'status' => $this->status,
            'skill_rating' => $this->skill_rating,
            'preferences' => $this->preferences,
            'created_at' => $this->created_at->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
