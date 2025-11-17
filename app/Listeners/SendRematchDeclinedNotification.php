<?php

namespace App\Listeners;

use App\Events\RematchDeclined;
use App\Models\Notification;

class SendRematchDeclinedNotification
{
    /**
     * Handle the event.
     */
    public function handle(RematchDeclined $event): void
    {
        $rematchRequest = $event->rematchRequest;

        Notification::create([
            'user_id' => $rematchRequest->requesting_user_id,
            'type' => 'rematch_declined',
            'data' => [
                'message' => 'Your rematch request was declined',
                'opponent_user_id' => $rematchRequest->opponent_user_id,
            ],
        ]);
    }
}
