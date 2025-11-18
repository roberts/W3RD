<?php

namespace App\Http\Resources;

use App\Models\Game\Action;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Action
 */
class ActionResource extends JsonResource
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
            'turn_number' => $this->turn_number,
            'action_type' => $this->action_type->value,
            'action_details' => $this->action_details,
            'player' => PlayerResource::make($this->player),
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
