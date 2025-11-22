<?php

namespace App\Matchmaking\Lobby;

use App\GameEngine\Lifecycle\Creation\GameBuilder;
use App\Models\Game\Lobby;

/**
 * Handles game creation from lobby when conditions are met
 */
class LobbyGameStarter
{
    public function __construct(
        protected GameBuilder $gameBuilder
    ) {}

    /**
     * Start a game from a lobby
     */
    public function startGame(Lobby $lobby): void
    {
        // Each player has their own client_id stored in lobby_players table
        // GameBuilder will read from there
        $this->gameBuilder->createFromLobby($lobby);
    }
}
