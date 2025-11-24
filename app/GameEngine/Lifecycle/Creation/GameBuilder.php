<?php

namespace App\GameEngine\Lifecycle\Creation;

use App\Enums\GameStatus;
use App\Enums\GameTitle;
use App\Enums\PlayerActivityState;
use App\GameEngine\Events\GameStarted;
use App\GameEngine\ModeRegistry;
use App\GameEngine\Player\PlayerActivityManager;
use App\Models\Games\Game;
use App\Models\Games\Mode;
use App\Models\Games\Player;
use App\Models\Matchmaking\Lobby;
use App\Models\Matchmaking\LobbyPlayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class GameBuilder
{
    public function __construct(
        protected ModeRegistry $modeRegistry
    ) {}

    /**
     * Create a game from matchmaking queue.
     *
     * @param  array<int, array{user_id: int, client_id: int}>  $playerData  Array of ['user_id' => int, 'client_id' => int]
     */
    public function createFromQueue(array $playerData, GameTitle $gameTitle, string $gameMode = 'standard'): Game
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
            $handler = $this->modeRegistry->resolve($game);
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
            // Get accepted players
            $lobbyPlayers = $lobby->players()
                ->where('status', 'accepted')
                ->with('user')
                ->get();

            // Create the game first
            $game = Game::create([
                'title_slug' => $lobby->title_slug,
                'mode_id' => $lobby->mode_id,
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
            $handler = $this->modeRegistry->resolve($game);
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

            // Set all players to IN_GAME state
            $activityService = app(PlayerActivityManager::class);
            foreach ($players as $player) {
                /** @var Player $player */
                $activityService->setState($player->user_id, PlayerActivityState::IN_GAME);
            }

            // Broadcast game started event
            broadcast(new GameStarted($game));

            return $game;
        });
    }

    /**
     * Create a game from a queue match by match ID.
     *
     * @param array<int, int> $playerIds
     */
    public function createFromQueueMatch(array $playerIds, string $matchId): Game
    {
        // Get game title and mode from match ID stored in Redis
        $matchKey = "queue:match:{$matchId}";
        $matchData = Redis::hgetall($matchKey);

        $gameTitle = GameTitle::from($matchData['game_title'] ?? 'connect-four');
        $gameMode = $matchData['game_mode'] ?? 'standard';

        // Prepare player data with each player's specific client_id
        $playerData = array_map(function ($userId) use ($matchData) {
            $clientKey = 'player_'.$userId.'_client';

            return [
                'user_id' => (int) $userId,
                'client_id' => (int) ($matchData[$clientKey] ?? 1), // Defaults to Gamer Protocol Web for AI
            ];
        }, $playerIds);

        // Create the game using the existing queue method
        $game = $this->createFromQueue($playerData, $gameTitle, $gameMode);

        // Set both players to IN_GAME state
        $activityService = app(PlayerActivityManager::class);
        foreach ($playerIds as $playerId) {
            $activityService->setState((int) $playerId, PlayerActivityState::IN_GAME);
        }

        // Clean up Redis
        Redis::del("queue:accept:{$matchId}");
        Redis::del($matchKey);

        // Remove players from queue and client tracking
        foreach ($playerIds as $playerId) {
            Redis::hdel('queue:timestamps', (string) $playerId);
            Redis::hdel('queue:clients', (string) $playerId);
        }

        return $game;
    }
}
