# Guide to Adding New Game Titles

This document provides a comprehensive guide to the architecture for implementing new game titles in the Gamer Protocol application. The structure is designed for scalability to 1,000+ games, testability, and ease of maintenance.

## Overview

The architecture follows a **trait-based composition model** where games inherit behavior through carefully designed traits rather than complex inheritance hierarchies. The system is built on four key principles:

1. **Separation of Concerns**: Game logic is separated into distinct components (Protocol, Config, Arbiter, Reporter, Handlers)
2. **Trait Composition**: Behavioral patterns (turns, timers, visibility) are mixed in via traits
3. **Handler Registry**: Actions are validated and applied through a configurable handler system
4. **Configuration-Driven**: Game variations are created by injecting different configs, not by subclassing

## Directory Structure

Each game resides in its own directory under `app/GameTitles/{GameTitle}/`. The standard structure is:

```text
app/GameTitles/Checkers/
├── CheckersProtocol.php      # Abstract Protocol: Coordinates components
├── CheckersConfig.php        # Config: Action Registry + Initial State
├── CheckersArbiter.php       # Arbiter: Win/Loss/Draw detection
├── CheckersReporter.php      # Reporter: Formats events for API/UI
├── CheckersBoard.php         # State DTO: Thematic game state object
├── CheckersPlayer.php        # State DTO: Player-specific state
├── Actions/                  # Action DTOs: Player intent declarations
│   ├── CheckersActionMapper.php # Mapper: API input → Action DTOs
│   ├── MovePiece.php
│   └── JumpPiece.php
├── Handlers/                 # Handlers: Action validation & execution
│   ├── MovePieceHandler.php
│   └── JumpPieceHandler.php
├── Enums/                    # Enums: Error codes, piece types
│   ├── CheckersActionError.php
│   └── CheckersPieceType.php
└── Modes/                    # Concrete Modes: Public game variants
    ├── StandardMode.php      # Standard rules (extends Protocol)
    └── TournamentMode.php    # Variant rules (extends Protocol)
```

## Core Components

### 1. The Protocol (`{GameTitle}Protocol.php`)

**Role**: Abstract base class that coordinates all game-specific components.

**Characteristics**:
- **Abstract class** (not instantiated directly)
- Extends genre base (`BaseBoardGameTitle` or `BaseCardGameTitle`)
- Implements `GameTitleContract`
- Orchestrates Config, Arbiter, Reporter, State classes

**Responsibilities**:
- Define game attributes (dynamic, timer, complexity)
- Inject dependencies via abstract methods (`getGameConfig()`, `getArbiter()`, `getReporter()`)
- Implement `createInitialState()` for game setup
- Provide state class and action mapper references
- **Does NOT** contain game logic—delegates to handlers via kernel

**Example Structure**:
```php
abstract class CheckersProtocol extends BaseBoardGameTitle implements GameTitleContract
{
    // Game attributes
    public static function getDynamic(): GameDynamic { return GameDynamic::ONE_VS_ONE; }
    
    // Abstract dependency injections
    abstract protected function getGameConfig(): CheckersConfig;
    abstract public function getArbiter(): CheckersArbiter;
    abstract protected function getReporter(): CheckersReporter;
    
    // State management
    public function createInitialState(string ...$playerUlids): object { ... }
    public function getStateClass(): string { return CheckersBoard::class; }
    public function getActionMapper(): string { return CheckersActionMapper::class; }
}
```

### 2. The Config (`{GameTitle}Config.php`)

**Role**: Configuration object defining action handlers and initial state parameters.

**Responsibilities**:
- Implements `GameConfigContract`
- Defines **Action Registry**: Maps action classes to handler classes
- Provides handler-specific rules (e.g., `['gravity' => true]` for Connect Four)
- Returns initial state configuration (board size, piece count, etc.)
- Can be customized via constructor for game variants

**Key Methods**:
- `getActionRegistry()`: Returns `[ActionClass => ['handler' => HandlerClass, 'label' => string, 'rules' => array]]`
- `getInitialStateConfig()`: Returns array of state parameters

**Example**:
```php
class ConnectFourConfig implements GameConfigContract
{
    public function __construct(
        protected array $additionalActions = [],
        protected array $stateConfig = ['columns' => 7, 'rows' => 6, 'connectCount' => 4]use App\Enums\GameErrorCode;
    ) {}

    public function getActionRegistry(): array
    {
        return [
            PlacePiece::class => [
                'handler' => PlacePieceHandler::class,
                'label' => 'Drop Disc',
                'rules' => ['gravity' => true],
            ],
        ];
    }
}
```

### 3. The Arbiter (`{GameTitle}Arbiter.php`)

**Role**: Win/loss/draw detection and outcome determination.

**Responsibilities**:
- Implements `GameArbiterContract`
- Analyzes game state to detect end conditions
- Returns `GameOutcome` objects (winner, losers, reason)
- Contains win-condition logic (e.g., "4 in a row", "capture all pieces")
- **Pure function**: Only reads state, doesn't modify it

**Key Method**:
- `checkOutcome(object $state, Game $game): ?GameOutcome`

**Example**:
```php
class ConnectFourArbiter implements GameArbiterContract
{
    public function checkOutcome(object $state, Game $game): ?GameOutcome
    {
        if ($this->hasConnectedPieces($state, $state->currentPlayerUlid, 4)) {
            return GameOutcome::win($state->currentPlayerUlid, 'connected_four');
        }
        if ($this->isBoardFull($state)) {
            return GameOutcome::draw('board_full');
        }
        return null; // Game continues
    }
}
```

### 4. The Reporter (`{GameTitle}Reporter.php`)

**Role**: Formats game data for API responses and human-readable summaries.

**Responsibilities**:use App\Enums\GameErrorCode;
- Implements `GameReporterContract`
- Provides public status information (scores, piece counts, etc.)
- Describes state changes from actions ("Piece promoted to King")
- Formats action summaries ("Player1 moved piece from C3 to D4")
- Generates finish details for completed games

**Key Methods**:
- `getPublicStatus(object $state)`: Returns array for API
- `describeStateChanges(Game $game, Action $action, object $state)`: Returns array of change descriptions
- `formatActionSummary(Action $action)`: Returns human-readable string

### 5. The Action Mapper (`{GameTitle}ActionMapper.php`)

**Role**: Converts raw API input into strongly-typed Action DTOs.

**Responsibilities**:
- Implements `ActionMapperContract`
- Maps action type strings to Action class constructors
- Validates required fields are present
- Throws `InvalidActionDataException` for malformed input
- Provides list of supported action types

**Example**:
```php
class CheckersActionMapper implements ActionMapperContract
{
    public static function create(string $actionType, array $data): GameActionContract
    {
        return match ($actionType) {
            'move_piece' => new MovePiece(
                fromRow: $data['from_row'],
                fromCol: $data['from_col'],
                toRow: $data['to_row'],
                toCol: $data['to_col']
            ),
            'jump_piece' => new JumpPiece(...),
            default => throw new InvalidActionDataException('Unknown action type')
        };
    }
}
```

### 6. State DTOs (Thematic Objects)

**Role**: Immutable data carriers representing game state.

**Naming Convention**: Use thematic names that reflect the game's domain:

| Category | Game State Name | Player State Name | Examples |
|----------|----------------|-------------------|----------|
| **Board Games** | `{GameTitle}Board` | `{GameTitle}Player` | `CheckersBoard`, `ChessBoard` |
| **Card Games** | `{GameTitle}Table` | `{GameTitle}Hand` | `HeartsTable`, `PokerHand` |
| **Strategy** | `{GameTitle}Map` | `{GameTitle}Faction` | `RiskMap`, `CivWorld` |
| **Trivia** | `{GameTitle}Stage` | `{GameTitle}Contestant` | `TriviaStage` |
| **Economy** | `{GameTitle}Market` | `{GameTitle}Portfolio` | `StockMarket` |

**Characteristics**:
- Use `readonly` properties for immutability
- Implement `fromArray()` and `toArray()` for serialization
- Contain **no business logic**—only data and helpers
- Must be JSON-serializable for database storage

**Example**:
```php
class CheckersBoard
{
    public function __construct(
        public readonly array $board,
        public readonly string $currentPlayerUlid,
        public readonly array $players,
        // ...
    ) {}
    
    public static function fromArray(array $data): self { ... }
    public function toArray(): array { ... }
}
```

### 7. Action DTOs

**Role**: Simple DTOs representing player intent.

**Characteristics**:
- Implement `GameActionContract`
- Use `readonly` properties
- Contain action parameters only (no logic)
- Provide `getType()` and `toArray()` methods

**Example**:
```php
class MovePiece implements GameActionContract
{
    public function __construct(
        public readonly int $fromRow,
        public readonly int $fromCol,
        public readonly int $toRow,
        public readonly int $toCol,
    ) {}
    
    public function getType(): string { return 'move_piece'; }
    public function toArray(): array { return [...]; }
}
```

### 8. Action Handlers

**Role**: Validate and execute actions on game state.

**Responsibilities**:
- Implement `GameActionHandlerInterface`
- Validate action legality in current state
- Apply action to state (return new state object)
- Calculate available options for action type

**Key Methods**:
- `validate(object $state, object $action): ValidationResult`
- `apply(object $state, object $action): object`
- `getAvailableOptions(object $state, string $playerUlid): array`

**Example**:
```php
class MovePieceHandler implements GameActionHandlerInterface
{
    public function validate(object $state, object $action): ValidationResult
    {
        if (!$this->isValidMove($state, $action)) {
            return ValidationResult::invalid('INVALID_MOVE', 'Move not allowed');
        }
        return ValidationResult::valid();
    }
    
    public function apply(object $state, object $action): object
    {
        // Return new state with move applied
    }
}
```

### 9. Game Modes

**Role**: Concrete implementations providing specific game variations.

**Characteristics**:
- Extend the abstract `{GameTitle}Protocol` class
- Inject specific Config/Arbiter/Reporter instances
- Define mode-specific rules or parameters
- Entry point for instantiation

**Example**:
```php
class StandardMode extends CheckersProtocol
{
    protected function getGameConfig(): CheckersConfig
    {
        return new CheckersConfig();
    }
    
    public function getArbiter(): CheckersArbiter
    {
        return new CheckersArbiter();
    }
    
    protected function getReporter(): CheckersReporter
    {
        return new CheckersReporter();
    }
}

class TournamentMode extends CheckersProtocol
{
    protected function getGameConfig(): CheckersConfig
    {
        return new CheckersConfig(
            timeLimitSeconds: 120,  // Longer time for tournaments
            gracePeriodSeconds: 0    // No grace period
        );
    }
    // ... same arbiter and reporter
}
```

## The GameKernel: Handler Registry

**Location**: `app/GameEngine/Kernel/GameKernel.php`

**Role**: Registry that manages action handlers and coordinates validation/application.

### Key Responsibilities

1. **Handler Initialization**: Constructs handlers from config's action registry
2. **Action Validation**: Routes actions to appropriate handler's `validate()` method
3. **Action Application**: Routes actions to appropriate handler's `apply()` method
4. **Available Actions**: Aggregates available options from all handlers

### Architecture

The kernel is **not** a complex orchestrator—it's a lean handler registry:

```php
class GameKernel
{
    protected array $handlers = []; // ActionClass => HandlerInstance
    
    public function __construct(protected GameConfigContract $config)
    {
        $this->initializeHandlers();
    }
    
    public function validateAction(object $state, object $action): ValidationResult
    {
        $handler = $this->handlers[get_class($action)] ?? null;
        return $handler ? $handler->validate($state, $action) 
                        : ValidationResult::invalid('INVALID_ACTION_TYPE');
    }
    
    public function applyAction(object $state, object $action): object
    {
        $handler = $this->handlers[get_class($action)] ?? null;
        return $handler ? $handler->apply($state, $action) : $state;
    }
    
    public function getAvailableActions(object $state, string $playerUlid): array
    {
        $options = [];
        foreach ($this->handlers as $actionClass => $handler) {
            $actionOptions = $handler->getAvailableOptions($state, $playerUlid);
            if (!empty($actionOptions)) {
                $options[$this->getActionKey($actionClass)] = $actionOptions;
            }
        }
        return $options;
    }
}
```

### Handler Initialization

Handlers are constructed from config with dependency injection and rule configuration:

```php
protected function initializeHandlers(): void
{
    foreach ($this->config->getActionRegistry() as $actionClass => $config) {
        $handlerClass = $config['handler'];
        $rules = $config['rules'] ?? [];
        
        // Container can inject dependencies, rules passed to constructor
        $this->handlers[$actionClass] = new $handlerClass($rules);
    }
}
```

### Why This Design?

**Scalability**: With 1,000+ games:
- Each game defines its own action registry in config
- Handlers are reusable across games (e.g., `PlacePieceHandler` works for multiple games)
- No central routing logic—configs define their own mappings
- Easy to add game-specific handlers without touching engine code

**Flexibility**: Game variations can:
- Inject custom handlers via config
- Add additional actions (e.g., PopOut mode in Connect Four)
- Override handler rules per mode (e.g., different gravity rules)

**Simplicity**: 
- ~110 lines of code
- Single responsibility: manage handler registry
- No game-specific logic
- Pure delegation pattern

## The Game Engine Structure

**Location**: `app/GameEngine/`

### Directory Organization

```
app/GameEngine/
├── Kernel/
│   └── GameKernel.php              # Handler registry
├── Interfaces/                      # Contracts
│   ├── GameTitleContract.php       # Game implementation interface
│   ├── GameConfigContract.php      # Config interface
│   ├── GameArbiterContract.php     # Arbiter interface
│   ├── GameReporterContract.php    # Reporter interface
│   ├── GameActionContract.php      # Action DTO interface
│   ├── GameActionHandlerInterface.php # Handler interface
│   └── ActionMapperContract.php    # Mapper interface
├── Actions/                         # Shared action DTOs
│   ├── PlacePiece.php              # Drop/place piece actions
│   ├── MovePiece.php               # Move existing piece
│   ├── JumpPiece.php               # Jump over pieces
│   ├── PlayCard.php                # Play a card
│   ├── PassCards.php               # Pass cards to others
│   └── ...                         # Other reusable actions
├── Handlers/                        # Shared handlers
│   ├── PlacePieceHandler.php       # Handles piece placement
│   └── ...                         # Other reusable handlers
├── Traits/                          # Behavioral composition
│   ├── Pacing/                     # When players act
│   ├── Sequence/                   # Turn order logic
│   ├── Visibility/                 # Information visibility
│   └── TimerExpired/               # Timeout behavior
├── Lifecycle/                       # Game lifecycle management
│   ├── Creation/                   # Game setup
│   ├── Progression/                # Turn/phase advancement
│   └── Conclusion/                 # Game ending logic
├── Timer/                           # Timer management
└── ValidationResult.php             # Validation response DTO
```

### Shared Actions vs. Game-Specific Actions

**Use Shared Actions** (`app/GameEngine/Actions/`) when:
- Action is conceptually similar across games
- Multiple games need the same validation logic
- Examples: `PlacePiece`, `MovePiece`, `PlayCard`, `DrawCard`

**Use Game-Specific Actions** (`app/GameTitles/{GameTitle}/Actions/`) when:
- Action is unique to one game
- Action has game-specific parameters
- Examples: `DealCards` (Hearts), `ClaimRemainingTricks` (Hearts)

**Guideline**: Start with game-specific actions. If 2+ games use the same pattern, promote to shared.

## Architecture Layers

The system is built on three inheritance layers that provide increasing specificity:

### Layer 1: Universal Base (`BaseGameTitle`)

**Location**: `app/GameTitles/BaseGameTitle.php`

**Purpose**: Universal foundation for **all** games (board, card, real-time, async).

**Key Features**:
- Implements `GameTitleContract` and `GameReporterContract`
- Initializes `GameKernel` with game config
- Hydrates state from database array
- Delegates action validation/application to kernel
- Provides deadline calculation: `Last Action Time + Time Limit + Grace Period`
- **Makes NO assumptions** about game type (no turns, timers, or boards assumed)

**What It Provides**:
```php
abstract class BaseGameTitle
{
    protected Game $game;
    protected object $gameState;
    protected GameKernel $kernel;
    
    // Delegates to kernel
    public function validateAction(object $state, object $action): ValidationResult;
    public function applyAction(object $state, object $action): object;
    
    // Abstract methods that subclasses must implement
    abstract protected function getGameStateClass(): string;
    abstract protected function getGameConfig(): GameConfigContract;
}
```

### Layer 2: Genre Bases (`BaseBoardGameTitle`, `BaseCardGameTitle`)

**Purpose**: Provide genre-specific behaviors through trait composition.

#### BaseBoardGameTitle

**Location**: `app/GameTitles/BaseBoardGameTitle.php`

**Includes Traits**:
- `SequentialTurns`: Turn-based progression logic
- `SynchronousPacing`: All players act in sequence
- `FullInformation`: All game state visible to all players
- `ForfeitOnTimerExpired`: Timer expiration causes forfeit

**Provides**:
- Default 60-second turn timer
- `isWithinBounds(row, col)` helper for grid validation
- Game attribute declarations (pacing, sequence, visibility)

#### BaseCardGameTitle

**Location**: `app/GameTitles/BaseCardGameTitle.php`

**Includes Traits**:
- `SequentialTurns`: Turn-based progression logic
- `SynchronousPacing`: All players act in sequence  
- `HiddenInformation`: Private state per player
- `ForfeitOnTimerExpired`: Timer expiration causes forfeit

**Provides**:
- Default 30-second turn timer
- Card utility methods: `createShuffledDeck()`, `getSuit()`, `getRank()`, `compareCards()`
- Deck management helpers

### Layer 3: Game Protocols (Abstract)

**Example**: `CheckersProtocol`, `ConnectFourProtocol`, `HeartsProtocol`

**Purpose**: Game-specific coordination without implementation.

**Characteristics**:
- Extends genre base (`BaseBoardGameTitle` or `BaseCardGameTitle`)
- Declares game attributes (dynamic, timer, complexity)
- Defines abstract methods for dependency injection
- Implements `createInitialState()` for game setup
- **Still abstract**—not instantiated directly

### Layer 4: Concrete Modes

**Example**: `StandardMode`, `SpeedMode`, `TournamentMode`

**Purpose**: Concrete implementations with specific configurations.

**Characteristics**:
- Extends game protocol
- Injects Config/Arbiter/Reporter instances
- Can override behavior for variations
- **Entry point** for game instantiation

**Inheritance Chain Example**:
```
GameTitleContract (interface)
    ↓
BaseGameTitle (abstract, universal)
    ↓
BaseBoardGameTitle (abstract, board game behaviors)
    ↓
CheckersProtocol (abstract, checkers-specific)
    ↓
StandardMode (concrete, standard rules)
```

## The Trait System: Composable Behaviors

Instead of complex inheritance hierarchies, the system uses **traits** to compose game behaviors. This allows mixing and matching patterns without rigid class structures.

### Core Behavioral Traits

**Location**: `app/GameEngine/Traits/`

#### Pacing Traits (`Traits/Pacing/`)
Control when and how players take actions:

- **`SynchronousPacing`**: All players act in sequence (turn-based)
- **`AsynchronousPacing`**: Players can act independently, out of order
- **`RealTimePacing`**: Players act simultaneously without waiting

**Methods Provided**: `isSynchronous()`, `canPlayerActNow()`

#### Sequence Traits (`Traits/Sequence/`)
Control turn order and progression:

- **`SequentialTurns`**: Players take turns in fixed order
  - Provides: `advanceTurn()`, `isPlayerTurn()`, `getCurrentPlayerIndex()`
- **`SimultaneousTurns`**: All players act at once (e.g., simultaneous reveal)
- **`FreeformSequence`**: No fixed turn order

#### Visibility Traits (`Traits/Visibility/`)
Control information visibility:

- **`FullInformation`**: All state visible to all players (Chess, Checkers)
  - Provides: `redactStateForPlayer()` (no-op)
- **`HiddenInformation`**: Some state hidden per player (Card games)
  - Provides: `redactStateForPlayer()` (removes private data)
- **`FogOfWar`**: Limited visibility based on position (Strategy games)

#### Timer Expiration Traits (`Traits/TimerExpired/`)
Control what happens when time runs out:

- **`ForfeitOnTimerExpired`**: Player forfeits on timeout
- **`SkipTurnOnTimerExpired`**: Turn skipped, game continues
- **`PointPenaltyOnTimerExpired`**: Player loses points but game continues

**Methods Provided**: `handleTimerExpired()`

### Trait Composition Examples

**Board Games** (Checkers, Chess, Connect Four):
```php
abstract class BaseBoardGameTitle extends BaseGameTitle
{
    use SequentialTurns;          // Turn-based
    use SynchronousPacing;        // One player at a time
    use FullInformation;          // Everyone sees everything
    use ForfeitOnTimerExpired;    // Timeout = forfeit
}
```

**Card Games** (Hearts, Spades, Poker):
```php
abstract class BaseCardGameTitle extends BaseGameTitle
{
    use SequentialTurns;          // Turn-based
    use SynchronousPacing;        // One player at a time
    use HiddenInformation;        // Private hands
    use ForfeitOnTimerExpired;    // Timeout = forfeit
}
```

**Custom Combination** (Real-time strategy game):
```php
abstract class BaseRTSGameTitle extends BaseGameTitle
{
    use FreeformSequence;         // No turns
    use AsynchronousPacing;       // Act anytime
    use FogOfWar;                 // Limited visibility
    use PointPenaltyOnTimerExpired; // Time pressure, not elimination
}
```

### Creating New Traits

To add new behavioral patterns:

1. Create trait in appropriate category folder
2. Define methods that implement the behavior
3. Document which methods are provided
4. Mix into genre bases or specific games

**Example**:
```php
// app/GameEngine/Traits/Sequence/AlternatingTeamTurns.php
trait AlternatingTeamTurns
{
    public function advanceTurn(Game $game): Game
    {
        // Custom team-based turn logic
    }
    
    public function isTeamTurn(string $teamId): bool
    {
        // Check if it's this team's turn
    }
}
```

## Step-by-Step: Adding a New Game

This walkthrough demonstrates adding a new game from scratch using **Tic-Tac-Toe** as an example.

### Step 1: Create Directory Structure

```bash
mkdir -p app/GameTitles/TicTacToe/{Actions,Handlers,Enums,Modes}
```

### Step 2: Create State DTO

**File**: `app/GameTitles/TicTacToe/TicTacToeBoard.php`

```php
<?php

declare(strict_types=1);

namespace App\GameTitles\TicTacToe;

class TicTacToeBoard
{
    public function __construct(
        public readonly array $board,           // 3x3 grid
        public readonly string $currentPlayerUlid,
        public readonly array $players,         // [ulid1, ulid2]
        public readonly ?string $winner = null,
    ) {}

    public static function createNew(string $playerOneUlid, string $playerTwoUlid): self
    {
        return new self(
            board: array_fill(0, 3, array_fill(0, 3, null)),
            currentPlayerUlid: $playerOneUlid,
            players: [$playerOneUlid, $playerTwoUlid],
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            board: $data['board'],
            currentPlayerUlid: $data['current_player_ulid'],
            players: $data['players'],
            winner: $data['winner'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'board' => $this->board,
            'current_player_ulid' => $this->currentPlayerUlid,
            'players' => $this->players,
            'winner' => $this->winner,
        ];
    }
}
```

### Step 3: Create Action DTO

**File**: `app/GameTitles/TicTacToe/Actions/PlaceMark.php`

```php
<?php

declare(strict_types=1);

namespace App\GameTitles\TicTacToe\Actions;

use App\GameEngine\Interfaces\GameActionContract;

class PlaceMark implements GameActionContract
{
    public function __construct(
        public readonly int $row,
        public readonly int $col,
    ) {}

    public function getType(): string
    {
        return 'place_mark';
    }

    public function toArray(): array
    {
        return [
            'row' => $this->row,
            'col' => $this->col,
        ];
    }
}
```

### Step 4: Create Action Handler

**File**: `app/GameTitles/TicTacToe/Handlers/PlaceMarkHandler.php`

```php
<?php

declare(strict_types=1);

namespace App\GameTitles\TicTacToe\Handlers;

use App\Enums\GameErrorCode;
use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\ValidationResult;
use App\GameTitles\TicTacToe\Actions\PlaceMark;
use App\GameTitles\TicTacToe\TicTacToeBoard;

class PlaceMarkHandler implements GameActionHandlerInterface
{
    public function validate(object $state, object $action): ValidationResult
    {
        if (!($state instanceof TicTacToeBoard)) {
            return ValidationResult::invalid(GameErrorCode::INVALID_STATE->value, 'Invalid state');
        }
        
        if (!($action instanceof PlaceMark)) {
            return ValidationResult::invalid(GameErrorCode::INVALID_ACTION_TYPE->value, 'Invalid action');
        }

        // Validate bounds
        if ($action->row < 0 || $action->row > 2 || $action->col < 0 || $action->col > 2) {
            return ValidationResult::invalid('OUT_OF_BOUNDS', 'Position out of bounds');
        }

        // Validate square is empty
        if ($state->board[$action->row][$action->col] !== null) {
            return ValidationResult::invalid('SQUARE_OCCUPIED', 'Square already occupied');
        }

        return ValidationResult::valid();
    }

    public function apply(object $state, object $action): object
    {
        $newBoard = $state->board;
        $newBoard[$action->row][$action->col] = $state->currentPlayerUlid;

        // Switch to next player
        $currentIndex = array_search($state->currentPlayerUlid, $state->players);
        $nextPlayerUlid = $state->players[($currentIndex + 1) % 2];

        return new TicTacToeBoard(
            board: $newBoard,
            currentPlayerUlid: $nextPlayerUlid,
            players: $state->players,
            winner: $state->winner,
        );
    }

    public function getAvailableOptions(object $state, string $playerUlid): array
    {
        if (!($state instanceof TicTacToeBoard) || $state->currentPlayerUlid !== $playerUlid) {
            return [];
        }

        $available = [];
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 3; $col++) {
                if ($state->board[$row][$col] === null) {
                    $available[] = ['row' => $row, 'col' => $col];
                }
            }
        }

        return ['positions' => $available];
    }
}
```

### Step 5: Create Action Mapper

**File**: `app/GameTitles/TicTacToe/Actions/TicTacToeActionMapper.php`

```php
<?php

declare(strict_types=1);

namespace App\GameTitles\TicTacToe\Actions;

use App\Exceptions\InvalidActionDataException;
use App\GameEngine\Interfaces\ActionMapperContract;
use App\GameEngine\Interfaces\GameActionContract;

class TicTacToeActionMapper implements ActionMapperContract
{
    public static function create(string $actionType, array $data): GameActionContract
    {
        if ($actionType !== 'place_mark') {
            throw new InvalidActionDataException(
                "Unknown action type: {$actionType}",
                'unknown_action_type',
                'tic-tac-toe',
                ['action_type' => $actionType]
            );
        }

        $requiredFields = ['row', 'col'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidActionDataException(
                    "Missing required field: {$field}",
                    'missing_required_field',
                    'tic-tac-toe',
                    ['field' => $field]
                );
            }
        }

        return new PlaceMark(
            row: (int) $data['row'],
            col: (int) $data['col']
        );
    }

    public static function validate(string $actionType, array $data): bool
    {
        return $actionType === 'place_mark' 
            && isset($data['row']) 
            && isset($data['col']);
    }

    public static function getSupportedActionTypes(): array
    {
        return ['place_mark'];
    }
}
```

### Step 6: Create Config

**File**: `app/GameTitles/TicTacToe/TicTacToeConfig.php`

```php
<?php

declare(strict_types=1);

namespace App\GameTitles\TicTacToe;

use App\GameEngine\Interfaces\GameConfigContract;
use App\GameTitles\TicTacToe\Actions\PlaceMark;
use App\GameTitles\TicTacToe\Handlers\PlaceMarkHandler;

class TicTacToeConfig implements GameConfigContract
{
    public function getActionRegistry(): array
    {
        return [
            PlaceMark::class => [
                'handler' => PlaceMarkHandler::class,
                'label' => 'Place Mark',
            ],
        ];
    }

    public function getInitialStateConfig(): array
    {
        return [];
    }
}
```

### Step 7: Create Arbiter

**File**: `app/GameTitles/TicTacToe/TicTacToeArbiter.php`

```php
<?php

declare(strict_types=1);

namespace App\GameTitles\TicTacToe;

use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameArbiterContract;
use App\Models\Game\Game;

class TicTacToeArbiter implements GameArbiterContract
{
    public function checkOutcome(object $state, Game $game): ?GameOutcome
    {
        if (!($state instanceof TicTacToeBoard)) {
            return null;
        }

        // Check rows, columns, diagonals for winner
        foreach ($state->players as $playerUlid) {
            if ($this->hasWon($state->board, $playerUlid)) {
                return GameOutcome::win($playerUlid, 'three_in_a_row');
            }
        }

        // Check for draw (board full)
        if ($this->isBoardFull($state->board)) {
            return GameOutcome::draw('board_full');
        }

        return null; // Game continues
    }

    private function hasWon(array $board, string $playerUlid): bool
    {
        // Check rows
        for ($i = 0; $i < 3; $i++) {
            if ($board[$i][0] === $playerUlid && 
                $board[$i][1] === $playerUlid && 
                $board[$i][2] === $playerUlid) {
                return true;
            }
        }

        // Check columns
        for ($i = 0; $i < 3; $i++) {
            if ($board[0][$i] === $playerUlid && 
                $board[1][$i] === $playerUlid && 
                $board[2][$i] === $playerUlid) {
                return true;
            }
        }

        // Check diagonals
        if ($board[0][0] === $playerUlid && 
            $board[1][1] === $playerUlid && 
            $board[2][2] === $playerUlid) {
            return true;
        }
        
        if ($board[0][2] === $playerUlid && 
            $board[1][1] === $playerUlid && 
            $board[2][0] === $playerUlid) {
            return true;
        }

        return false;
    }

    private function isBoardFull(array $board): bool
    {
        foreach ($board as $row) {
            if (in_array(null, $row, true)) {
                return false;
            }
        }
        return true;
    }
}
```

### Step 8: Create Reporter

**File**: `app/GameTitles/TicTacToe/TicTacToeReporter.php`

```php
<?php

declare(strict_types=1);

namespace App\GameTitles\TicTacToe;

use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameReporterContract;
use App\Models\Game\Action;
use App\Models\Game\Game;

class TicTacToeReporter implements GameReporterContract
{
    public function getPublicStatus(object $state): array
    {
        if (!($state instanceof TicTacToeBoard)) {
            return [];
        }

        return [
            'moves_made' => $this->countMoves($state->board),
            'moves_remaining' => 9 - $this->countMoves($state->board),
        ];
    }

    public function describeStateChanges(Game $game, Action $action, object $state): array
    {
        return []; // Simple game, no special state changes to report
    }

    public function formatActionSummary(Action $action): string
    {
        $details = $action->action_details;
        return sprintf(
            'Placed mark at position (%d, %d)',
            $details['row'] ?? '?',
            $details['col'] ?? '?'
        );
    }

    public function formatFinishDetails(Game $game, GameOutcome $outcome, object $state): array
    {
        return [
            'outcome' => $outcome->type->value,
            'reason' => $outcome->reason,
            'total_moves' => $this->countMoves($state->board),
        ];
    }

    private function countMoves(array $board): int
    {
        $count = 0;
        foreach ($board as $row) {
            foreach ($row as $cell) {
                if ($cell !== null) {
                    $count++;
                }
            }
        }
        return $count;
    }
}
```

### Step 9: Create Protocol

**File**: `app/GameTitles/TicTacToe/TicTacToeProtocol.php`

```php
<?php

declare(strict_types=1);

namespace App\GameTitles\TicTacToe;

use App\Enums\GameAttributes\GameComplexity;
use App\Enums\GameAttributes\GameDynamic;
use App\Enums\GameAttributes\GameTimer;
use App\Exceptions\InvalidGameConfigurationException;
use App\GameEngine\Interfaces\GameTitleContract;
use App\GameTitles\BaseBoardGameTitle;
use App\GameTitles\TicTacToe\Actions\TicTacToeActionMapper;

abstract class TicTacToeProtocol extends BaseBoardGameTitle implements GameTitleContract
{
    // Game Attributes
    public static function getDynamic(): GameDynamic
    {
        return GameDynamic::ONE_VS_ONE;
    }

    public static function getTimer(): GameTimer
    {
        return GameTimer::FORFEIT;
    }

    public static function getAdditionalAttributes(): array
    {
        return [
            GameComplexity::class => GameComplexity::CASUAL,
        ];
    }

    protected const DEFAULT_TURN_TIME_SECONDS = 15;

    // Abstract dependency injections
    abstract protected function getGameConfig(): TicTacToeConfig;
    abstract public function getArbiter(): TicTacToeArbiter;
    abstract protected function getReporter(): TicTacToeReporter;

    // State management
    protected function getGameStateClass(): string
    {
        return TicTacToeBoard::class;
    }

    public function createInitialState(string ...$playerUlids): object
    {
        if (count($playerUlids) !== 2) {
            throw new InvalidGameConfigurationException(
                'Tic-Tac-Toe requires exactly 2 players',
                'tic-tac-toe',
                ['player_count' => count($playerUlids)]
            );
        }

        return TicTacToeBoard::createNew($playerUlids[0], $playerUlids[1]);
    }

    public function getStateClass(): string
    {
        return TicTacToeBoard::class;
    }

    public function getActionMapper(): string
    {
        return TicTacToeActionMapper::class;
    }

    public static function getRules(): array
    {
        return [
            'title' => 'Tic-Tac-Toe',
            'description' => 'Get three marks in a row horizontally, vertically, or diagonally.',
            'sections' => [
                [
                    'title' => 'How to Play',
                    'content' => 'Players alternate placing their mark (X or O) in empty squares. First to get three in a row wins.',
                ],
            ],
        ];
    }
}
```

### Step 10: Create Mode

**File**: `app/GameTitles/TicTacToe/Modes/StandardMode.php`

```php
<?php

declare(strict_types=1);

namespace App\GameTitles\TicTacToe\Modes;

use App\GameTitles\TicTacToe\TicTacToeArbiter;
use App\GameTitles\TicTacToe\TicTacToeConfig;
use App\GameTitles\TicTacToe\TicTacToeProtocol;
use App\GameTitles\TicTacToe\TicTacToeReporter;

class StandardMode extends TicTacToeProtocol
{
    protected function getGameConfig(): TicTacToeConfig
    {
        return new TicTacToeConfig();
    }

    public function getArbiter(): TicTacToeArbiter
    {
        return new TicTacToeArbiter();
    }

    protected function getReporter(): TicTacToeReporter
    {
        return new TicTacToeReporter();
    }
}
```

### Step 11: Register the Game

**Important**: Game modes must be registered in the `ModeRegistry` for the game engine to resolve them properly. The `ModeRegistry` is responsible for mapping game instances to their corresponding mode handler classes.

#### 11.1: Add to GameTitle Enum

Add to `app/Enums/GameTitle.php`:

```php
enum GameTitle: string
{
    // ... existing games
    case TIC_TAC_TOE = 'tic-tac-toe';
}
```

#### 11.2: Register in ModeRegistry

Add to `app/GameEngine/ModeRegistry.php` in the `$modeMap` array:

```php
protected array $modeMap = [
    // ... existing mappings
    'tic-tac-toe' => [
        'standard' => TicTacToeStandardMode::class,
    ],
];
```

**Note**: The `ModeRegistry` uses the game's `title_slug` and `mode_slug` to resolve the correct mode handler class. The structure is:

```php
'game-title-slug' => [
    'mode-slug' => ModeClassName::class,
]
```

Each game can have multiple modes registered (e.g., `'standard'`, `'tournament'`, `'speed'`), and the registry will instantiate the appropriate mode class when requested by the game engine.

Don't forget to add the import at the top of `ModeRegistry.php`:

```php
use App\GameTitles\TicTacToe\Modes\StandardMode as TicTacToeStandardMode;
```

### Step 12: Create Tests

**File**: `tests/Unit/Games/TicTacToe/TicTacToeArbiterTest.php`

```php
<?php

use App\GameTitles\TicTacToe\TicTacToeArbiter;
use App\GameTitles\TicTacToe\TicTacToeBoard;

test('detects horizontal win', function () {
    $board = [
        ['player1', 'player1', 'player1'],
        [null, null, null],
        [null, null, null],
    ];
    
    $state = new TicTacToeBoard(
        board: $board,
        currentPlayerUlid: 'player1',
        players: ['player1', 'player2'],
    );
    
    $arbiter = new TicTacToeArbiter();
    $outcome = $arbiter->checkOutcome($state, mockGame());
    
    expect($outcome)->not->toBeNull()
        ->and($outcome->winnerUlid)->toBe('player1');
});

test('detects draw when board is full', function () {
    $board = [
        ['player1', 'player2', 'player1'],
        ['player1', 'player2', 'player2'],
        ['player2', 'player1', 'player1'],
    ];
    
    $state = new TicTacToeBoard(
        board: $board,
        currentPlayerUlid: 'player1',
        players: ['player1', 'player2'],
    );
    
    $arbiter = new TicTacToeArbiter();
    $outcome = $arbiter->checkOutcome($state, mockGame());
    
    expect($outcome)->not->toBeNull()
        ->and($outcome->type->value)->toBe('draw');
});
```

You now have a complete Tic-Tac-Toe implementation following the architecture!

## Common Patterns and Best Practices

This structure allows for easy expansion in several directions:

1.  **New Game Modes**: Create a new class in `Modes/` that extends `{GameTitle}Protocol` and injects a different `Config` configuration (e.g., "Chaos Mode" with different piece movement).
2.  **Rule Variations**: Create a new `Config` class (e.g., `FrenchCheckersConfig.php`) and swap it in via a Mode.
3.  **AI Agents**: The State DTOs provide a clean, standardized data structure that is easy to feed into AI models or heuristic algorithms.
4.  **Frontend Independence**: Since all logic and text descriptions are encapsulated in the backend, the frontend can be generic, rendering the board based on the State and displaying rules based on `{GameTitle}Config`.

## The Attribute System: Declarative Engine Logic

To achieve maximum code reuse and allow the core game engine to handle common gameplay mechanics, we use a declarative **Attribute System**. Instead of writing `if/else` logic for every game, each `...Protocol.php` file declares its characteristics through a series of static methods. The engine then uses these attributes to dynamically apply the correct logic.

This system is the key to rapidly developing hundreds of titles without rewriting core engine components.

### How It Works

The `GameTitleContract` requires each game to implement a set of `get...()` methods that return a specific `GameAttribute` enum. The `GameActionController` (our "Game Kernel") reads these attributes and alters its behavior accordingly.

### The Four Core Engine Components

The attribute system currently drives four major, scalable components within the game engine:

#### 1. Game Visibility & The `GameRedactor`

*   **Attribute**: `getVisibility(): GameVisibility`
*   **Values**: `FULL_INFORMATION`, `HIDDEN_INFORMATION`
*   **Engine Logic**: When preparing an API response, the engine checks this attribute.
    *   If `FULL_INFORMATION` (like Checkers), the entire game state is returned as-is via the `NullGameRedactor`.
    *   If `HIDDEN_INFORMATION` (like Hearts), the request is routed through a game-specific `GameRedactor` (e.g., `HeartsRedactor`) which is responsible for removing sensitive data (like other players' hands) before sending the state to the user.
*   **Scalability**: To add a new game with hidden info (e.g., Poker), you simply create a `PokerRedactor`, and the `GameRedactorServiceProvider` will automatically use it based on the game's `getVisibility()` attribute.

#### 2. Game Pacing & The `TimerExpiredJob`

*   **Attribute**: `getPacing(): GamePacing`
*   **Values**: `NONE`, `RELAXED`, `STANDARD`, `BLITZ`
*   **Engine Logic**: After a player's turn, the `GameActionController` checks this attribute.
    *   It dispatches a `TimerExpiredJob` with a delay corresponding to the pacing value (e.g., 15 seconds for `BLITZ`, 5 minutes for `RELAXED`).
    *   If the pacing is `NONE`, no job is dispatched.
*   **Scalability**: Adding new time controls (e.g., a `TOURNAMENT` pace) is as simple as adding a case to the enum and a corresponding delay in the controller's `dispatchTimerExpiredJob` method.

#### 3. Game Sequence & Turn Management

*   **Attribute**: `getSequence(): GameSequence`
*   **Values**: `TURN_BASED`, `REAL_TIME`, `PHASE_BASED`
*   **Engine Logic**: The `GameActionController` calls an `advanceTurn()` method after each action.
    *   If `TURN_BASED`, it increments the game's `turn_number`.
    *   If `REAL_TIME`, it does nothing, as turns are not sequential.
    *   If `PHASE_BASED`, it can delegate to a more complex state machine to determine the next phase or player.
*   **Scalability**: This allows the same engine to handle a traditional board game, a fast-paced real-time game, and a complex card game with distinct phases (passing, playing, scoring) without changing the core action-handling loop.

#### 4. Game Dynamics & The `ConclusionManager`

*   **Attribute**: `getDynamic(): GameDynamic`
*   **Values**: `ONE_VS_ONE`, `LAST_MAN_STANDING`, `SCORE_BASED`, `FREE_FOR_ALL`
*   **Engine Logic**: The `checkEndCondition` method has been **removed** from individual game protocols. Instead, the `GameActionController` calls the `ConclusionManager` after every move.
    *   This service checks the game's `getDynamic()` attribute.
    *   It then applies the correct logic to determine a winner (e.g., checking for one active player for `LAST_MAN_STANDING`, or comparing scores for `SCORE_BASED`).
*   **Scalability**: To add a new win condition (e.g., `CAPTURE_THE_FLAG`), you add a case to the `GameDynamic` enum and implement the corresponding logic within a new private method in the `ConclusionManager`. No changes are needed in the game protocols themselves.

## Common Patterns and Best Practices

### Action Handler Patterns

#### Pattern 1: Configurable Rules

Handlers can accept rules to modify behavior for different game modes.

#### Pattern 2: Multi-Step Validation

Break complex validation into clear, testable steps for maintainability.

#### Pattern 3: Immutable State Updates

Always return new state objects to prevent unexpected mutations.

### Avoid Common Pitfalls

- ❌ **Don't** put game logic in Protocol classes
- ✅ **Do** put logic in Handler classes
- ❌ **Don't** mutate state DTOs directly
- ✅ **Do** return new instances
- ❌ **Don't** mix responsibilities (e.g., Arbiter doing reporting)
- ✅ **Do** separate concerns properly

## Architecture Benefits

### For 1,000+ Games
- No core changes needed for new games
- Isolated testing per game
- Reusable components across games
- Clear contracts ensure consistency

### For Rapid Development
- Copy-paste-modify from existing games
- Mix-and-match behaviors via traits
- Override only what's needed
- Clear separation of concerns

### For Maintainability
- Single responsibility principle
- Dependency injection throughout
- Immutable state objects
- Type safety with PHP 8.3+
- Highly testable architecture

## Summary Checklist

When adding a new game, create:

- [ ] State DTO(s) with `fromArray()` and `toArray()`
- [ ] Action DTO(s) implementing `GameActionContract`
- [ ] Action Mapper implementing `ActionMapperContract`
- [ ] Handler(s) implementing `GameActionHandlerInterface`
- [ ] Config implementing `GameConfigContract`
- [ ] Arbiter implementing `GameArbiterContract`
- [ ] Reporter implementing `GameReporterContract`
- [ ] Protocol (abstract) extending genre base
- [ ] Mode(s) (concrete) extending protocol
- [ ] Tests for handlers, arbiter, and integration
- [ ] Registration in `GameTitle` enum
- [ ] **Registration in `ModeRegistry`** (add mode mapping in `$modeMap` array)

**Critical**: The `ModeRegistry` is essential for the game engine to resolve mode handlers. Without registering your mode in `app/GameEngine/ModeRegistry.php`, the game will fail to load when players attempt to create or interact with it. Each game title and its modes must be explicitly mapped in the registry's `$modeMap` array.

Follow this architecture for a scalable, maintainable foundation supporting unlimited game titles.

## Game Documentation Standard

Every game title must include a comprehensive documentation file named `{gametitle}.md` (lowercase) located in its root directory (e.g., `app/GameTitles/TicTacToe/tictactoe.md`). This document serves as the source of truth for frontend developers and AI agents.

### Required Structure

#### 1. Header & Description
Start with the game title and a high-level description of the game.

```markdown
# Tic-Tac-Toe API Documentation

## Game Description
A two-player turn-based game...
```

#### 2. Rules
List the core rules clearly.

```markdown
## Rules
1. **Players**: 2 players (X vs O).
2. **Objective**: Get 3 in a row...
```

#### 3. Game Modes
Describe each available mode, its specific win conditions, and valid actions.

```markdown
## Game Modes

### Standard Mode
- **Grid**: 3x3.
- **Win Condition**: 3 in a row.
- **Actions**: `place_mark`.
```

#### 4. API Endpoints
This is the most critical section. You must document the specific JSON payloads for every action type supported by the game.

**Required Sub-sections:**
- **Submit Game Action (`POST`)**: List every `action_type` with its required `action_details` JSON structure.
- **Get Game Details (`GET`)**: Show a full example of the response, including the `game_state`.
- **List Games (`GET`)**: Standard list response.
- **Get Available Actions (`GET`)**: Show how the `options` endpoint returns valid moves for the current state.

**Example Action Documentation:**
```markdown
### 1. Submit Game Action
**Endpoint**: `POST /api/v1/games/{game_id}/actions`

#### Request Body

**Action: Place Mark**
```json
{
    "action_type": "place_mark",
    "action_details": {
        "row": 1,
        "col": 1
    }
}
```
```

#### 5. Game State JSON Specification
Provide a table detailing every field in the `game_state` object. This is essential for frontend rendering.

```markdown
## Game State JSON Specification

| Field | Type | Description |
|-------|------|-------------|
| `board` | `Array<Array<string\|null>>` | 3x3 Grid... |
| `currentPlayerUlid` | `string` | ... |
```

### Best Practices
- **Be Verbose**: Include full JSON examples.
- **Spec-Driven**: Write for developers who have never seen the code.
- **Update Frequently**: If you add an action or change the state structure, update this doc immediately.

