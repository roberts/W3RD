<?php

namespace App\Jobs;

use App\Matchmaking\Events\ProposalCancelled;
use App\Models\Matchmaking\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckAndCancelPendingProposals implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Find all pending rematch requests where user is involved
        $pendingRematches = Proposal::where('status', 'pending')
            ->where(function ($query) {
                $query->where('requesting_user_id', $this->userId)
                    ->orWhere('opponent_user_id', $this->userId);
            })
            ->get();

        if ($pendingRematches->isEmpty()) {
            return;
        }

        foreach ($pendingRematches as $rematch) {
            $rematch->update(['status' => 'cancelled']);

            // Determine reason based on who triggered cancellation
            $reason = $rematch->requesting_user_id === $this->userId
                ? 'requester_unavailable'
                : 'opponent_unavailable';

            event(new ProposalCancelled($rematch, $reason));

            Log::info('Rematch cancelled due to player unavailability', [
                'rematch_request_id' => $rematch->id,
                'cancelled_by_user_id' => $this->userId,
                'reason' => $reason,
            ]);
        }
    }
}
