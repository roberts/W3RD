<?php

namespace App\Events;

use App\Models\Game\Game;
use App\Models\Game\Proposal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RematchAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Proposal $rematchRequest,
        public Game $newGame
    ) {}
}
