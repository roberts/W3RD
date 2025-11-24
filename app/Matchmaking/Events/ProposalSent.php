<?php

namespace App\Matchmaking\Events;

use App\Models\Matchmaking\Proposal;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProposalSent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Proposal $proposal
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("users.{$this->proposal->opponent_user_id}");
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'proposal_ulid' => $this->proposal->ulid,
            'type' => $this->proposal->type->value,
            'title_slug' => $this->proposal->title_slug,
            'requesting_user_id' => $this->proposal->requesting_user_id,
            'opponent_user_id' => $this->proposal->opponent_user_id,
            'status' => $this->proposal->status->value,
            'expires_at' => optional($this->proposal->expires_at)?->toIso8601String(),
        ];
    }
}
