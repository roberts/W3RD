# Game Engine Traits

This directory contains trait-based implementations of game behavior patterns, replacing the previous Driver-based architecture.

## Architecture

Traits provide game behavior directly on GameTitle classes, eliminating the need for:
- Separate Driver interfaces
- Manager classes
- Factory methods
- Service provider driver registration

## Structure

### Sequence/ - Turn Order Patterns
- **SequentialTurns**: Players take turns one after another (Chess, Checkers)
- **SimultaneousTurns**: All players act at once (Rock Paper Scissors)
- **PhaseBasedTurns**: Game progresses through distinct phases (Hearts, Poker)
- **InterleavedTurns**: Players act based on priority/initiative (Magic: The Gathering)

### Pacing/ - Time Management Patterns
- **SynchronousPacing**: Short timers, players stay online (Blitz Chess, Hearthstone)
- **AsynchronousPacing**: Long timers, play-by-mail style (Words with Friends)
- **RealtimePacing**: Sub-second input required (Fighting games, FPS)
- **TickBasedPacing**: Server-driven state updates (Clash of Clans)

### Visibility/ - Information Patterns
- **FullInformation**: All players see identical state (Chess, Go)
- **HiddenInformation**: Private hands/resources (Poker, Hearts)
- **FogOfWar**: Vision-based visibility (StarCraft, Age of Empires)
- **AsymmetricInformation**: Role-based visibility (Among Us, Werewolf)

## Usage

### Basic Usage in Base Classes

```php
use App\GameEngine\Traits\Sequence\SequentialTurns;
use App\GameEngine\Traits\Pacing\SynchronousPacing;
use App\GameEngine\Traits\Visibility\FullInformation;

abstract class BaseBoardGameTitle extends BaseGameTitle
{
    use SequentialTurns;
    use SynchronousPacing;
    use FullInformation;
    
    // Enum methods remain as pure metadata
    public static function getPacing(): GamePacing
    {
        return GamePacing::TURN_BASED_SYNC;
    }
}
```

### Overriding Trait Behavior

Games can override trait methods for custom logic:

```php
abstract class HeartsProtocol extends BaseCardGameTitle
{
    use PhaseBasedTurns;
    
    // Override for phase-specific turn logic
    public function isPlayerTurn(Game $game, User $player): bool
    {
        $phase = $game->game_state['current_phase'];
        
        if ($phase === 'passing') {
            return !$this->hasPlayerPassedCards($game, $player);
        }
        
        return $game->current_player_id === $player->id;
    }
}
```

### GameKernel Integration

GameKernel now calls methods directly on the GameTitle:

```php
class GameKernel
{
    public function __construct(
        private GameConfigContract $config,
        public GameTitleContract $gameTitle,  // Directly uses trait methods
        public TimerExpiredDriver $timerExpiredDriver,
    ) {}
    
    public function advanceGame(Game $game): Game
    {
        $game = $this->gameTitle->advanceTurn($game);
        $this->gameTitle->startTurnTimer($game);
        return $game;
    }
}
```

## Benefits

1. **Simplicity**: ~600 lines of code eliminated (drivers, managers, factories)
2. **Performance**: Direct method calls instead of interface dispatch
3. **Clarity**: Behavior is directly on the game class
4. **Flexibility**: Easy to override any method for custom logic
5. **Discoverability**: IDE autocomplete shows all available methods
6. **No Abstraction Overhead**: Zero runtime cost for trait composition

## Enums Still Used

Game attribute enums (GamePacing, GameSequence, GameVisibility) are retained as **pure metadata**:
- API responses describe game characteristics
- Frontend can adapt UI based on game type
- Matchmaking can filter by game attributes
- Analytics and reporting use consistent categories

The enums no longer map to implementations - traits provide the behavior directly.
