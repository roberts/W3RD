<?php

namespace App\Http\Resources;

use App\Models\Game\RematchRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RematchRequest
 */
class RematchRequestResource extends JsonResource
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
            'status' => $this->status,
            'expires_at' => $this->expires_at,
        ];
    }
}
