<?php

namespace App\Listeners;

use App\Events\ProposalAccepted;
use App\Models\Alert;

class SendProposalAcceptedAlert
{
    /**
     * Handle the event.
     */
    public function handle(ProposalAccepted $event): void
    {
        $proposal = $event->proposal;
        $newGame = $event->newGame;

        Alert::create([
            'user_id' => $proposal->requesting_user_id,
            'type' => 'rematch_accepted',
            'data' => [
                'message' => 'Your rematch request was accepted',
                'new_game_ulid' => $newGame->ulid,
                'opponent_user_id' => $proposal->opponent_user_id,
            ],
        ]);
    }
}
