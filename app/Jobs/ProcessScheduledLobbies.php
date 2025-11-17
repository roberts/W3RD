<?php

namespace App\Jobs;

use App\Enums\LobbyStatus;
use App\Models\Lobby;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessScheduledLobbies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Find all scheduled lobbies that are due to start
        $dueLobbies = Lobby::where('status', LobbyStatus::PENDING)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($dueLobbies as $lobby) {
            $this->processScheduledLobby($lobby);
        }
    }

    private function processScheduledLobby(Lobby $lobby): void
    {
        // Check if minimum players requirement is met
        if ($lobby->hasMinimumPlayers()) {
            // Start the game
            $this->startGame($lobby);
        } else {
            // Cancel the lobby - not enough players
            $lobby->markAsCancelled();
            \Log::info("Cancelled scheduled lobby {$lobby->ulid} - insufficient players");

            // TODO: Notify players that the scheduled game was cancelled
        }
    }

    private function startGame(Lobby $lobby): void
    {
        $lobby->markAsReady();

        // TODO: Create Game and GamePlayer records
        // TODO: Broadcast GameStarted event to all accepted players
        // TODO: Mark lobby as completed

        \Log::info("Started scheduled game for lobby {$lobby->ulid}");
    }
}
