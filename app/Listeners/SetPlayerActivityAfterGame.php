<?php

namespace App\Listeners;

use App\Enums\PlayerActivityState;
use App\GameEngine\Events\GameCompleted;
use App\GameEngine\Player\PlayerActivityManager;

class SetPlayerActivityAfterGame
{
    public function __construct(
        protected PlayerActivityManager $activityService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(GameCompleted $event): void
    {
        foreach ($event->game->players as $player) {
            $this->activityService->setState($player->user_id, PlayerActivityState::IDLE);
        }
    }
}
