<?php

namespace App\Listeners;

use App\Events\RematchCancelled;
use App\Models\Alert;
use App\Models\Auth\User;

class SendRematchCancelledAlert
{
    /**
     * Handle the event.
     */
    public function handle(RematchCancelled $event): void
    {
        $rematch = $event->rematchRequest;

        $message = match ($event->reason) {
            'opponent_unavailable', 'opponent_left' => 'Your rematch request was cancelled - opponent joined another game',
            'requester_unavailable' => 'Rematch request cancelled - requester joined another game',
            'expired' => 'Rematch request expired',
            default => 'Rematch request was cancelled',
        };

        // Notify requester (if they're human)
        $requester = User::find($rematch->requesting_user_id);
        if ($requester && ! $requester->isAgent()) {
            Alert::create([
                'user_id' => $rematch->requesting_user_id,
                'type' => 'rematch_cancelled',
                'data' => [
                    'message' => $message,
                    'rematch_request_id' => $rematch->id,
                    'reason' => $event->reason,
                ],
            ]);
        }

        // Don't notify agent opponents
        $opponent = User::find($rematch->opponent_user_id);
        if ($opponent && ! $opponent->isAgent()) {
            Alert::create([
                'user_id' => $rematch->opponent_user_id,
                'type' => 'rematch_cancelled',
                'data' => [
                    'message' => $message,
                    'rematch_request_id' => $rematch->id,
                    'reason' => $event->reason,
                ],
            ]);
        }
    }
}
