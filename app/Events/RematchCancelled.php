<?php

namespace App\Events;

use App\Models\Game\RematchRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RematchCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public RematchRequest $rematchRequest,
        public string $reason // 'opponent_unavailable', 'requester_unavailable', 'expired', 'opponent_left'
    ) {}
}
