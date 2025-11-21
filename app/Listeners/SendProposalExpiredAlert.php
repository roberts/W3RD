<?php

namespace App\Listeners;

use App\Events\ProposalExpired;
use App\Models\Alert;

class SendProposalExpiredAlert
{
    /**
     * Handle the event.
     */
    public function handle(ProposalExpired $event): void
    {
        $proposal = $event->proposal;

        Alert::create([
            'user_id' => $proposal->requesting_user_id,
            'type' => 'rematch_expired',
            'data' => [
                'message' => 'Your rematch request has expired',
                'opponent_user_id' => $proposal->opponent_user_id,
            ],
        ]);
    }
}
