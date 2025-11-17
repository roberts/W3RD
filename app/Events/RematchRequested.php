<?php

namespace App\Events;

use App\Models\Game\RematchRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RematchRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public RematchRequest $rematchRequest
    ) {}
}
