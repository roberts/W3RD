<?php

namespace App\Events;

use App\Models\Game\Proposal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProposalCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Proposal $proposal,
        public string $reason // 'opponent_unavailable', 'requester_unavailable', 'expired', 'opponent_left'
    ) {}
}
