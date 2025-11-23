<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Game action options resource for available player actions.
 */
class GameActionOptionsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'options' => $this->resource['actions'],
            'is_your_turn' => $this->resource['isYourTurn'],
            'phase' => $this->resource['phase']->value ?? 'active',
            'deadline' => $this->resource['deadline']->toIso8601String(),
            'timelimit_seconds' => $this->resource['timelimit'],
        ];
    }
}
