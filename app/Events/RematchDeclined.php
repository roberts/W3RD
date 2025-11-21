<?php

namespace App\Events;

use App\Models\Game\Proposal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RematchDeclined
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Proposal $rematchRequest
    ) {}
}
