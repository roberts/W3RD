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
