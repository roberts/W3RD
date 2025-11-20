<?php

namespace App\Listeners;

use App\Enums\PlayerActivityState;
use App\Events\GameCompleted;
use App\Services\PlayerActivityService;

class SetPlayerActivityAfterGame
{
    public function __construct(
        protected PlayerActivityService $activityService
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
