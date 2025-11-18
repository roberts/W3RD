<?php

namespace App\Services;

use App\Enums\GameStatus;
use App\Enums\GameTitle;
use App\Events\GameStarted;
use App\Models\Game\Game;
use App\Models\Game\Lobby;
use App\Models\Game\LobbyPlayer;
use App\Models\Game\Mode;
use App\Models\Game\Player;
use App\Providers\GameServiceProvider;
use Illuminate\Support\Facades\DB;

class GameCreationService
{
    /**
     * Create a game from quickplay match.
     *
     * @param  array  $playerData  Array of ['user_id' => int, 'client_id' => int]
     */
    public function createFromQuickplay(array $playerData, GameTitle $gameTitle, string $gameMode = 'standard'): Game
    {
        return DB::transaction(function () use ($playerData, $gameTitle, $gameMode) {
            // Get or create mode
            $mode = Mode::firstOrCreate([
                'title_slug' => $gameTitle,
                'slug' => $gameMode,
            ], [
                'name' => ucfirst($gameMode),
                'description' => ucfirst($gameMode).' mode',
            ]);

            // Create the game first to get player ULIDs
            $game = Game::create([
                'title_slug' => $gameTitle,
                'mode_id' => $mode->id,
                'creator_id' => $playerData[0]['user_id'], // First player is creator
                'status' => GameStatus::ACTIVE,
                'game_state' => [], // Temporary empty state
            ]);

            // Create player records
            $colors = ['red', 'yellow', 'blue', 'green'];
            /** @var Player[] $players */
            $players = [];
            foreach ($playerData as $index => $player) {
                $players[] = $game->players()->create([
                    'user_id' => $player['user_id'],
                    'client_id' => $player['client_id'],
                    'position_id' => $index + 1,
                    'color' => $colors[$index % count($colors)],
                ]);
            }

            // Initialize game state using mode handler with player ULIDs
            $handler = GameServiceProvider::getMode($game);
            $playerUlids = [];
            foreach ($players as $player) {
                /** @var Player $player */
                $playerUlids[] = $player->ulid;
            }
            $initialState = $handler->createInitialState(...$playerUlids);

            // Update game with proper initial state
            $game->update([
                'game_state' => $initialState,
            ]);

            // Broadcast game started event
            broadcast(new GameStarted($game));

            return $game;
        });
    }

    /**
     * Create a game from a lobby.
     */
    public function createFromLobby(Lobby $lobby): Game
    {
        return DB::transaction(function () use ($lobby) {
            // Get or create mode
            $mode = Mode::firstOrCreate([
                'title_slug' => $lobby->game_title,
                'slug' => $lobby->game_mode ?? 'standard',
            ], [
                'name' => ucfirst($lobby->game_mode ?? 'standard'),
                'description' => ucfirst($lobby->game_mode ?? 'standard').' mode',
            ]);

            // Get accepted players
            $lobbyPlayers = $lobby->players()
                ->where('status', 'accepted')
                ->with('user')
                ->get();

            // Create the game first
            $game = Game::create([
                'title_slug' => $lobby->game_title,
                'mode_id' => $mode->id,
                'creator_id' => $lobby->host_id,
                'status' => GameStatus::ACTIVE,
                'game_state' => [], // Temporary empty state
            ]);

            // Create player records using each player's stored client_id
            $colors = ['red', 'yellow', 'blue', 'green'];
            /** @var Player[] $players */
            $players = [];
            foreach ($lobbyPlayers as $index => $lobbyPlayer) {
                /** @var LobbyPlayer $lobbyPlayer */
                $players[] = $game->players()->create([
                    'user_id' => $lobbyPlayer->user_id,
                    'client_id' => $lobbyPlayer->client_id ?? 1, // Defaults to Gamer Protocol Web for AI agents
                    'position_id' => $index + 1,
                    'color' => $colors[$index % count($colors)],
                ]);
            }

            // Initialize game state using mode handler with player ULIDs
            $handler = GameServiceProvider::getMode($game);
            $playerUlids = [];
            foreach ($players as $player) {
                /** @var Player $player */
                $playerUlids[] = $player->ulid;
            }
            $initialState = $handler->createInitialState(...$playerUlids);

            // Update game with proper initial state
            $game->update([
                'game_state' => $initialState,
            ]);

            // Update lobby status and link to game
            $lobby->update([
                'status' => 'completed',
                'game_id' => $game->id,
            ]);

            // Broadcast game started event
            broadcast(new GameStarted($game));

            return $game;
        });
    }
}
