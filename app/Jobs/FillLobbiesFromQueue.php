<?php

namespace App\Jobs;

use App\Matchmaking\Lobby\LobbyQueueFiller;
use App\Models\Matchmaking\Lobby;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Background job that fills public lobbies with players from the queue.
 * Delegates all filling logic to LobbyQueueFiller.
 */
class FillLobbiesFromQueue implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ?LobbyQueueFiller $lobbyQueueFiller = null
    ) {
        $this->lobbyQueueFiller = $lobbyQueueFiller ?? app(LobbyQueueFiller::class);
    }

    /**
     * Execute the job - find and fill public lobbies.
     */
    public function handle(): void
    {
        // Get all public, non-scheduled, pending lobbies that need players
        $lobbies = Lobby::where('is_public', true)
            ->where('status', 'pending')
            ->whereNull('scheduled_at')
            ->with(['players'])
            ->get();

        foreach ($lobbies as $lobby) {
            $this->lobbyQueueFiller->tryFillLobby($lobby);
        }
    }
}
