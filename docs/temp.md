# Temporary TODO: Lobby & Quickplay Integration Items

This document outlines the remaining integration tasks for the Lobby and Quickplay matchmaking system implementation.

## 1. Game Creation Integration

**Location**: Multiple controllers need to integrate with the existing game creation logic.

### Files that need updates:
- `app/Http/Controllers/Api/V1/QuickplayController.php` (method: `createGame`)
- `app/Http/Controllers/Api/V1/LobbyPlayerController.php` (method: `startGame`)
- `app/Jobs/ProcessScheduledLobbies.php` (method: `startGame`)

### Required Implementation:

When a match is ready to start (either from Quickplay acceptance or Lobby conditions being met), the system needs to:

1. **Create a Game record** using the existing `Game` model
   - Determine which game implementation to use based on `game_title` enum
   - Set up initial game state using the appropriate `BaseGameState` subclass
   - Store game configuration (mode, rules, etc.)

2. **Create GamePlayer records** for each participant
   - Link each user to the game
   - Assign player positions/colors as needed
   - Set initial player state using appropriate `BasePlayerState` subclass

3. **Update Lobby status** (for lobby games)
   - Mark lobby as `completed` after game creation
   - Clean up any pending invitations

### Example pseudocode:
```php
private function createGame(array $playerIds, string $matchId): void
{
    // 1. Determine game title from context
    $gameTitle = GameTitle::VALIDATE_FOUR; // Get from match data
    
    // 2. Create Game record
    $game = Game::create([
        'game_title' => $gameTitle,
        'game_mode' => $gameMode ?? 'standard',
        'status' => GameStatus::ACTIVE,
        'state' => $initialGameState, // Use appropriate game implementation
        // ... other fields
    ]);
    
    // 3. Create GamePlayer records
    foreach ($playerIds as $index => $playerId) {
        GamePlayer::create([
            'game_id' => $game->id,
            'user_id' => $playerId,
            'position' => $index,
            'state' => $initialPlayerState,
        ]);
    }
    
    // 4. Clean up Redis/Lobby
    Redis::del("quickplay:accept:{$matchId}");
    // or for lobbies: $lobby->markAsCompleted();
    
    // 5. Broadcast GameStarted event
    broadcast(new GameStarted($game));
}
```

---

## 2. AI Agent Integration

**Location**: `app/Jobs/ProcessQuickplayQueue.php` (method: `matchWithAI`)

### Current Status:
The method currently logs that it would match with AI but doesn't actually implement the logic.

### Required Implementation:

1. **Call SchedulingService** to find an available AI agent
   ```php
   $aiAgent = app(SchedulingService::class)->findAvailableAgent($gameTitle, $skillLevel);
   ```

2. **Create game with AI opponent** 
   - Use the AI agent's user account as the second player
   - Follow same game creation flow as human-vs-human matches
   - May need to set a flag indicating this is an AI opponent

3. **Handle AI availability failure**
   - If no AI is available, return user to queue or show appropriate error
   - Consider implementing a fallback queue for AI-pending matches

### Example implementation:
```php
private function matchWithAI(int $userId, GameTitle $gameTitle, string $mode, string $queueKey): void
{
    // Remove from queue
    Redis::zrem($queueKey, $userId);
    Redis::hdel('quickplay:timestamps', $userId);

    // Find AI agent
    $skillLevel = $this->getUserSkillLevel($userId, $gameTitle);
    $aiAgent = app(SchedulingService::class)->findAvailableAgent(
        gameTitle: $gameTitle,
        skillLevel: $skillLevel
    );
    
    if (!$aiAgent) {
        Log::warning("No AI agent available for user {$userId}");
        // Consider re-queueing or notifying user
        return;
    }
    
    // Create game with AI opponent
    $this->createGame([$userId, $aiAgent->user_id], $gameTitle, $mode, true);
    
    Log::info("Matched user {$userId} with AI agent {$aiAgent->id}");
}
```

---

## 3. GameStarted Event Implementation

**Location**: Need to create `app/Events/GameStarted.php`

### Required Implementation:

Create a broadcastable event that notifies all players when a game is ready to begin.

### Event Structure:
```php
<?php

namespace App\Events;

use App\Models\Game\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Game $game
    ) {
    }

    public function broadcastOn(): array
    {
        // Broadcast to each player in the game
        $channels = [];
        foreach ($this->game->players as $player) {
            $channels[] = new PrivateChannel('App.Models.User.'.$player->user_id);
        }
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'game' => [
                'ulid' => $this->game->ulid,
                'game_title' => $this->game->game_title->value,
                'game_mode' => $this->game->game_mode,
                'status' => $this->game->status->value,
                'players' => $this->game->players->map(function ($player) {
                    return [
                        'user_id' => $player->user_id,
                        'user_name' => $player->user->name,
                        'position' => $player->position,
                    ];
                }),
                'state' => $this->game->state,
            ],
        ];
    }
}
```

### Usage:
Once implemented, this event should be broadcast in all three locations where games are created:
- `QuickplayController::createGame()`
- `LobbyPlayerController::startGame()`
- `ProcessScheduledLobbies::startGame()`

---

## Implementation Priority

1. **GameStarted Event** (Highest Priority)
   - Required by all other components
   - Clean interface for frontend integration
   
2. **Game Creation Integration** (High Priority)
   - Core functionality for both systems
   - Unblocks testing of full flow
   
3. **AI Agent Integration** (Medium Priority)
   - Required for complete Quickplay functionality
   - Can be temporarily bypassed in testing

---

## Testing Considerations

Once these items are implemented, ensure:

1. **Integration tests** cover the full flow from queue/lobby → game creation → event broadcast
2. **AI matchmaking tests** verify proper agent selection and game creation
3. **Event tests** confirm proper broadcasting to all game participants
4. **Edge case tests** handle failures gracefully (no AI available, game creation fails, etc.)

---

**Last Updated**: 2025-11-16
**Status**: Pending Implementation
**Related Branch**: `003-lobby-and-matchmaking`
