<?php

namespace App\Jobs;

use App\Matchmaking\Orchestrators\ProposalOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireProposals implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(ProposalOrchestrator $proposalOrchestrator): void
    {
        $proposalOrchestrator->expireOldProposals();
    }
}
