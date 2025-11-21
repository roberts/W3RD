<?php

namespace App\Listeners;

use App\Events\ProposalDeclined;
use App\Models\Alert;

class SendProposalDeclinedAlert
{
    /**
     * Handle the event.
     */
    public function handle(ProposalDeclined $event): void
    {
        $proposal = $event->proposal;

        Alert::create([
            'user_id' => $proposal->requesting_user_id,
            'type' => 'rematch_declined',
            'data' => [
                'message' => 'Your rematch request was declined',
                'opponent_user_id' => $proposal->opponent_user_id,
            ],
        ]);
    }
}
