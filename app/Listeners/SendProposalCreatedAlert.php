<?php

namespace App\Listeners;

use App\Matchmaking\Events\ProposalCreated;
use App\Models\Alert;

class SendProposalCreatedAlert
{
    /**
     * Handle the event.
     */
    public function handle(ProposalCreated $event): void
    {
        $proposal = $event->proposal;

        Alert::create([
            'user_id' => $proposal->opponent_user_id,
            'type' => 'rematch_requested',
            'data' => [
                'message' => 'You have a rematch request',
                'rematch_request_id' => $proposal->id,
                'original_game_id' => $proposal->original_game_id,
                'requesting_user_id' => $proposal->requesting_user_id,
                'expires_at' => $proposal->expires_at->toIso8601String(),
            ],
        ]);
    }
}
