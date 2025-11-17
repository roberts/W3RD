<?php

namespace App\Listeners;

use App\Events\RematchRequested;
use App\Models\Notification;

class SendRematchRequestNotification
{
    /**
     * Handle the event.
     */
    public function handle(RematchRequested $event): void
    {
        $rematchRequest = $event->rematchRequest;

        Notification::create([
            'user_id' => $rematchRequest->opponent_user_id,
            'type' => 'rematch_requested',
            'data' => [
                'message' => 'You have a rematch request',
                'rematch_request_id' => $rematchRequest->id,
                'original_game_id' => $rematchRequest->original_game_id,
                'requesting_user_id' => $rematchRequest->requesting_user_id,
                'expires_at' => $rematchRequest->expires_at->toIso8601String(),
            ],
        ]);
    }
}
