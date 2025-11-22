<?php

namespace App\Jobs;

use App\GameEngine\Lifecycle\Creation\GameBuilder;
use App\Matchmaking\Enums\LobbyStatus;
use App\Models\Matchmaking\Lobby;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessScheduledLobbies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(GameBuilder $gameBuilder): void
    {
        // Find all scheduled lobbies that are due to start
        $dueLobbies = Lobby::where('status', LobbyStatus::PENDING)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($dueLobbies as $lobby) {
            $this->processScheduledLobby($lobby, $gameBuilder);
        }
    }

    private function processScheduledLobby(Lobby $lobby, GameBuilder $gameBuilder): void
    {
        // Check if minimum players requirement is met
        if ($lobby->hasMinimumPlayers()) {
            // Start the game
            $this->startGame($lobby, $gameBuilder);
        } else {
            // Cancel the lobby - not enough players
            $lobby->markAsCancelled();
            \Log::info("Cancelled scheduled lobby {$lobby->ulid} - insufficient players");

            // TODO: Notify players that the scheduled game was cancelled
        }
    }

    private function startGame(Lobby $lobby, GameBuilder $gameBuilder): void
    {
        $gameBuilder->createFromLobby($lobby);
    }
}
