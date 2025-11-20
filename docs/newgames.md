# Guide to Adding New Game Titles

This document outlines the standard architecture and separation of concerns for implementing new game titles in the Gamer Protocol application. The structure is designed for scalability, testability, and ease of maintenance.

## Directory Structure

Each game resides in its own directory under `App/Games/{GameTitle}/`. The standard structure is as follows:

```text
App/Games/Checkers/
├── CheckersProtocol.php      # Coordinator: The main entry point
├── CheckersConfig.php        # Configuration: Action Registry + Static Rules Text
├── CheckersArbiter.php       # Judge: Win/Draw detection & Rule enforcement
├── CheckersReporter.php      # Reporting: Formats game events for API & logs/analytics
├── CheckersBoard.php         # State: Thematic DTO for the board/game state
├── CheckersPlayer.php        # State: Thematic DTO for individual player state
├── Actions/                  # The "Words": DTOs representing player intent
│   ├── CheckersActionMapper.php # Translator: Maps raw input to Action DTOs
│   ├── MovePiece.php
│   └── JumpPiece.php
├── Handlers/                 # The "Verbs": Execution Logic
│   ├── MovePieceHandler.php
│   └── JumpPieceHandler.php
├── Enums/                    # Constants: Error codes, Piece types, etc.
│   ├── CheckersActionError.php
│   └── CheckersPieceType.php
└── Modes/                    # Variations: Public faces of the game
    ├── StandardMode.php
    └── SpeedMode.php
```

## Component Roles

### 1. The Protocol (`{GameTitle}Protocol.php`)
*   **Role**: The central hub that connects the Game Engine to the specific game implementation.
*   **Responsibilities**:
    *   Extends `BaseBoardGameTitle` (or similar base).
    *   Implements `GameTitleContract`.
    *   Initializes the `CheckersBoard` (or relevant state).
    *   Orchestrates the flow by calling the Arbiter and Handlers.
    *   **Does NOT** contain specific rule logic (e.g., "4 in a row wins") or configuration.

### 2. The Config (`{GameTitle}Config.php`)
*   **Role**: The static configuration and knowledge base of the game.
*   **Responsibilities**:
    *   Implements `GameConfigContract`.
    *   Defines the **Action Registry** (mapping Action classes to Handlers).
    *   Provides static text descriptions of rules (e.g., for UI tooltips or help screens).
    *   Defines initial state parameters (e.g., board size, piece count).

### 3. The Arbiter (`{GameTitle}Arbiter.php`)
*   **Role**: The judge and rule enforcer.
*   **Responsibilities**:
    *   Implements `GameArbiterContract`.
    *   Analyzes a `CheckersBoard` to determine if a Win, Loss, or Draw has occurred.
    *   Returns `GameOutcome` objects.
    *   Contains complex validation logic that spans multiple turns or board states.

### 4. The Reporter (`{GameTitle}Reporter.php`)
*   **Role**: The storyteller and analyst.
*   **Responsibilities**:
    *   Implements `GameReporterContract`.
    *   Formats game events into human-readable logs.
    *   Generates analytics data (e.g., "Average moves per game", "Piece capture rate").
    *   Decouples logging/analytics from the core game logic.

### 5. The Mapper (`{GameTitle}Mapper.php`)
*   **Role**: The translator.
*   **Responsibilities**:
    *   Implements `ActionMapperContract`.
    *   Converts raw API input (arrays) into strongly-typed Action DTOs.
    *   Performs structural validation (e.g., "Does this request have a 'column' field?").

### 6. Thematic State DTOs
*   **Role**: Immutable (or effectively immutable) data carriers.
*   **Naming Convention**: Use thematic names instead of generic `GameState`.
*   **Responsibilities**:
    *   Hold the current state of the board, scores, and players.
    *   Contain **no business logic** (only getters, setters, and serialization methods).
    *   Must be serializable to JSON for database storage.

#### Recommended State Names by Category

| Category | Game State Name | Player State Name | Example |
| :--- | :--- | :--- | :--- |
| **Board Games** | `{GameTitle}Board` | `{GameTitle}Player` | `CheckersBoard`, `ChessBoard` |
| **Card Games** | `{GameTitle}Table` | `{GameTitle}Hand` | `HeartsTable`, `PokerHand` |
| **Strategy / War** | `{GameTitle}Map` or `{GameTitle}World` | `{GameTitle}Faction` | `RiskMap`, `CivWorld` |
| **Trivia / Quiz** | `{GameTitle}Stage` or `{GameTitle}Session` | `{GameTitle}Contestant` | `TriviaStage`, `JeopardySession` |
| **Economy** | `{GameTitle}Market` or `{GameTitle}Exchange` | `{GameTitle}Portfolio` | `StockMarket`, `CryptoExchange` |
| **RPG / Adventure** | `{GameTitle}Campaign` or `{GameTitle}Dungeon` | `{GameTitle}Character` | `DndCampaign`, `ZeldaDungeon` |

### 7. Actions & Handlers
*   **Actions (`Actions/`)**: Simple DTOs representing a player's intent (e.g., `MovePiece`).
*   **Handlers (`Handlers/`)**: The logic that executes an action.
    *   Validates the action against the current state.
    *   Mutates the State (returns a new instance).
    *   Returns a `ValidationResult`.

### 8. Modes (`Modes/`)
*   **Role**: The entry points for different variations of the game.
*   **Responsibilities**:
    *   Extend the `{GameTitle}Protocol` class.
    *   Inject specific `Config` and `Arbiter` implementations into the Protocol.
    *   Example: `SpeedMode` might use `StandardConfig` but override the time limit.

## Protocol Architecture

The system uses a layered inheritance model to provide helpful tools without forcing a specific game structure.

### 1. The Contract (`GameTitleContract`)
This interface defines the absolute minimum requirements for the Game Engine to interact with any game. It is agnostic to the game's genre or mechanics.
*   **Lifecycle**: `createInitialState` (Start), `checkEndCondition` (End).
*   **Reflection**: `getStateClass`, `getActionMapper` (Tells the engine what classes to use).
*   **Interaction**: `getAvailableActions` (What can the user do?), `getPublicStatus` (What can the user see?).

### 2. The Base (`BaseGameTitle`)
This abstract class implements `GameTitleContract` and provides the "plumbing" connecting the Game Model to the Game Engine. It is **not over-abstracted**; it does not assume the game has turns, players, or a board.
*   **State Hydration**: Automatically converts the raw database array into your typed `GameState` object.
*   **Kernel Integration**: Sets up the `GameKernel` to handle action validation and application.
*   **Time Management**: Provides a standard `getActionDeadline` calculation (Last Action Time + Time Limit + Network Grace Period).
*   **Reporting**: Provides default (empty) implementations for reporting, so you only implement what you need.

### 3. Genre Categories
We provide intermediate base classes for common game genres. These are optional starting points that provide "Turn-Based" or "Sequential" logic relevant to that category.

*   **BaseBoardGameTitle**:
    *   **Turn-Based Defaults**: Sets a default 60-second turn timer.
    *   **Grid Helpers**: Provides `isWithinBounds(row, col)` for grid-based logic.
*   **BaseCardGameTitle**:
    *   **Deck Management**: Helpers for `createShuffledDeck`, `getSuit`, `getRank`.
    *   **Turn-Based Defaults**: Sets a default 30-second turn timer.

### 4. Extensibility & Overrides
All underlying functions in these base classes are designed to be extended or overwritten.
*   **Protocol Level**: Your `CheckersProtocol` can override `getTimelimit` to change the time, or `getActionDeadline` to change how time is calculated entirely.
*   **Mode Level**: Specific modes (e.g., `SpeedCheckers`) can further override these behaviors, allowing for radical changes (e.g., a Real-Time mode for a Turn-Based game) without rewriting the core logic.

## The Game Engine (`App/GameEngine`)

The core Game Engine provides the infrastructure that supports these game implementations.

*   **Interfaces**: Defines the contracts (`GameTitleContract`, `GameConfigContract`, `GameArbiterContract`) that ensure all games behave consistently.
*   **Kernel**: Manages the game loop, turn transitions, and persistence.
*   **Shared Actions**: Common actions like `MovePiece`, `PlacePiece`, `DrawCard` are defined globally in `App/GameEngine/Actions` to prevent code duplication. Games should use these whenever possible.

## Future Expansions

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
*   **Values**: `PERFECT_INFORMATION`, `HIDDEN_INFORMATION`
*   **Engine Logic**: When preparing an API response, the engine checks this attribute.
    *   If `PERFECT_INFORMATION` (like Checkers), the entire game state is returned as-is via the `NullGameRedactor`.
    *   If `HIDDEN_INFORMATION` (like Hearts), the request is routed through a game-specific `GameRedactor` (e.g., `HeartsRedactor`) which is responsible for removing sensitive data (like other players' hands) before sending the state to the user.
*   **Scalability**: To add a new game with hidden info (e.g., Poker), you simply create a `PokerRedactor`, and the `GameRedactorServiceProvider` will automatically use it based on the game's `getVisibility()` attribute.

#### 2. Game Pacing & The `TimeoutJob`

*   **Attribute**: `getPacing(): GamePacing`
*   **Values**: `NONE`, `RELAXED`, `STANDARD`, `BLITZ`
*   **Engine Logic**: After a player's turn, the `GameActionController` checks this attribute.
    *   It dispatches a `TimeoutJob` with a delay corresponding to the pacing value (e.g., 15 seconds for `BLITZ`, 5 minutes for `RELAXED`).
    *   If the pacing is `NONE`, no job is dispatched.
*   **Scalability**: Adding new time controls (e.g., a `TOURNAMENT` pace) is as simple as adding a case to the enum and a corresponding delay in the controller's `dispatchTimeoutJob` method.

#### 3. Game Sequence & Turn Management

*   **Attribute**: `getSequence(): GameSequence`
*   **Values**: `TURN_BASED`, `REAL_TIME`, `PHASE_BASED`
*   **Engine Logic**: The `GameActionController` calls an `advanceTurn()` method after each action.
    *   If `TURN_BASED`, it increments the game's `turn_number`.
    *   If `REAL_TIME`, it does nothing, as turns are not sequential.
    *   If `PHASE_BASED`, it can delegate to a more complex state machine to determine the next phase or player.
*   **Scalability**: This allows the same engine to handle a traditional board game, a fast-paced real-time game, and a complex card game with distinct phases (passing, playing, scoring) without changing the core action-handling loop.

#### 4. Game Dynamics & The `GameConclusionService`

*   **Attribute**: `getDynamic(): GameDynamic`
*   **Values**: `ONE_VS_ONE`, `LAST_MAN_STANDING`, `SCORE_BASED`, `FREE_FOR_ALL`
*   **Engine Logic**: The `checkEndCondition` method has been **removed** from individual game protocols. Instead, the `GameActionController` calls the `GameConclusionService` after every move.
    *   This service checks the game's `getDynamic()` attribute.
    *   It then applies the correct logic to determine a winner (e.g., checking for one active player for `LAST_MAN_STANDING`, or comparing scores for `SCORE_BASED`).
*   **Scalability**: To add a new win condition (e.g., `CAPTURE_THE_FLAG`), you add a case to the `GameDynamic` enum and implement the corresponding logic within a new private method in the `GameConclusionService`. No changes are needed in the game protocols themselves.
