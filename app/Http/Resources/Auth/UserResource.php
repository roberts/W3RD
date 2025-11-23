<?php

namespace App\Http\Resources\Auth;

use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'username' => $this->username,
            'name' => $this->name,
            'avatar' => $this->avatar?->image?->url, // @phpstan-ignore-line
            'bio' => $this->when($this->bio !== null, $this->bio),
            'social_links' => $this->when($this->social_links !== null, $this->social_links),
        ];
    }
}
