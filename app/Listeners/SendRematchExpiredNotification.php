<?php

namespace App\Listeners;

use App\Events\RematchExpired;
use App\Models\Notification;

class SendRematchExpiredNotification
{
    /**
     * Handle the event.
     */
    public function handle(RematchExpired $event): void
    {
        $rematchRequest = $event->rematchRequest;

        Notification::create([
            'user_id' => $rematchRequest->requesting_user_id,
            'type' => 'rematch_expired',
            'data' => [
                'message' => 'Your rematch request has expired',
                'opponent_user_id' => $rematchRequest->opponent_user_id,
            ],
        ]);
    }
}
