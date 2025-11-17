<?php

namespace App\Listeners;

use App\Events\RematchExpired;
use App\Models\Alert;

class SendRematchExpiredAlert
{
    /**
     * Handle the event.
     */
    public function handle(RematchExpired $event): void
    {
        $rematchRequest = $event->rematchRequest;

        Alert::create([
            'user_id' => $rematchRequest->requesting_user_id,
            'type' => 'rematch_expired',
            'data' => [
                'message' => 'Your rematch request has expired',
                'opponent_user_id' => $rematchRequest->opponent_user_id,
            ],
        ]);
    }
}
