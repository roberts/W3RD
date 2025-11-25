<?php

namespace App\Jobs;

use App\Enums\GameTitle;
use App\Matchmaking\Queue\MatchmakingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Background job that processes the matchmaking queue.
 * Delegates all matching logic to MatchmakingService.
 */
class ProcessMatchmakingQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private ?MatchmakingService $matchmakingService = null
    ) {
        $this->matchmakingService = $matchmakingService ?? app(MatchmakingService::class);
    }

    /**
     * Process all game queues across all titles and modes.
     */
    public function handle(): void
    {
        $gameTitles = GameTitle::cases();

        foreach ($gameTitles as $gameTitle) {
            foreach (['standard', 'blitz', 'rapid'] as $mode) {
                $this->matchmakingService->processQueue($gameTitle, $mode);
            }
        }
    }
}
