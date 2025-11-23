<?php

namespace App\GameEngine\Events;

use App\Models\Games\Game;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameActivityEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Game $game,
        public string $activityType,
        public array $data = []
    ) {}
}
