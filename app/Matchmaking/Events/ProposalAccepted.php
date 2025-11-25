<?php

namespace App\Matchmaking\Events;

use App\Models\Games\Game;
use App\Models\Matchmaking\Proposal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProposalAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Proposal $proposal,
        public Game $newGame
    ) {}
}
