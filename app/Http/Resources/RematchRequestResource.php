<?php

namespace App\Http\Resources;

use App\Models\Auth\User;
use App\Models\Game\RematchRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Redis;

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
        // Check if opponent is an agent with active cooldown
        $opponentUserId = $this->requesting_user_id === $request->user()?->id 
            ? $this->opponent_user_id 
            : $this->requesting_user_id;
        
        $opponentUser = User::find($opponentUserId);
        $autoAcceptExpected = false;
        
        if ($opponentUser && $opponentUser->isAgent()) {
            $cooldownKey = "agent:{$opponentUser->id}:cooldown";
            $autoAcceptExpected = Redis::exists($cooldownKey);
        }

        return [
            'ulid' => $this->ulid,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'auto_accept_expected' => $autoAcceptExpected,
        ];
    }
}
