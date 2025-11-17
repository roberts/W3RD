<?php

namespace App\Listeners;

use App\Events\RematchAccepted;
use App\Models\Notification;

class SendRematchAcceptedNotification
{
    /**
     * Handle the event.
     */
    public function handle(RematchAccepted $event): void
    {
        $rematchRequest = $event->rematchRequest;
        $newGame = $event->newGame;

        Notification::create([
            'user_id' => $rematchRequest->requesting_user_id,
            'type' => 'rematch_accepted',
            'data' => [
                'message' => 'Your rematch request was accepted',
                'new_game_ulid' => $newGame->ulid,
                'opponent_user_id' => $rematchRequest->opponent_user_id,
            ],
        ]);
    }
}
