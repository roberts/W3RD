<?php

namespace App\Jobs;

use App\Enums\LobbyStatus;
use App\Models\Game\Lobby;
use App\Services\GameCreationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessScheduledLobbies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(GameCreationService $gameCreationService): void
    {
        // Find all scheduled lobbies that are due to start
        $dueLobbies = Lobby::where('status', LobbyStatus::PENDING)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($dueLobbies as $lobby) {
            $this->processScheduledLobby($lobby, $gameCreationService);
        }
    }

    private function processScheduledLobby(Lobby $lobby, GameCreationService $gameCreationService): void
    {
        // Check if minimum players requirement is met
        if ($lobby->hasMinimumPlayers()) {
            // Start the game
            $this->startGame($lobby, $gameCreationService);
        } else {
            // Cancel the lobby - not enough players
            $lobby->markAsCancelled();
            \Log::info("Cancelled scheduled lobby {$lobby->ulid} - insufficient players");

            // TODO: Notify players that the scheduled game was cancelled
        }
    }

    private function startGame(Lobby $lobby, GameCreationService $gameCreationService): void
    {
        $lobby->markAsReady();

        // Each player's client_id is already stored in their lobby_player record
        $gameCreationService->createFromLobby($lobby);

        \Log::info("Started scheduled game for lobby {$lobby->ulid}");
    }
}
