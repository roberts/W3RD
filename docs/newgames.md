# Guide to Adding New Game Titles

This document outlines the standard architecture and separation of concerns for implementing new game titles in the Gamer Protocol application. The structure is designed for scalability, testability, and ease of maintenance.

## Directory Structure

Each game resides in its own directory under `App/Games/{GameTitle}/`. The standard structure is as follows:

```text
App/Games/Checkers/
├── BaseCheckers.php          # Coordinator: Wires Config, Engine, and State
├── CheckersRules.php         # Configuration: Action Registry + Static Rules Text
├── CheckersRuleEngine.php    # Logic: Win/Draw detection & Rule enforcement
├── CheckersReporter.php      # Reporting: Formats game events for logs/analytics
├── GameState.php             # DTO: Pure Data Object for the board/game state
├── PlayerState.php           # DTO: Pure Data Object for individual player state
├── Actions/                  # Factory: Creates Action objects from raw data
│   └── ActionFactory.php
├── Handlers/                 # Logic: Individual Action implementations
│   ├── MovePieceHandler.php
│   └── JumpPieceHandler.php
└── Modes/                    # Variations: Public faces of the game
    ├── StandardMode.php
    └── SpeedMode.php
```

## Component Roles

### 1. The Coordinator (`Base{GameTitle}.php`)
*   **Role**: The central hub that connects the Game Engine to the specific game implementation.
*   **Responsibilities**:
    *   Extends `BaseBoardGameTitle` (or similar base).
    *   Implements `GameTitleContract`.
    *   Initializes the `GameState`.
    *   Orchestrates the flow by calling the Rule Engine and Handlers.
    *   **Does NOT** contain specific rule logic (e.g., "4 in a row wins") or configuration.

### 2. The Rules Configuration (`{GameTitle}Rules.php`)
*   **Role**: The static configuration and knowledge base of the game.
*   **Responsibilities**:
    *   Implements `GameConfigInterface`.
    *   Defines the **Action Registry** (mapping Action classes to Handlers).
    *   Provides static text descriptions of rules (e.g., for UI tooltips or help screens).
    *   Defines initial state parameters (e.g., board size, piece count).

### 3. The Rule Engine (`{GameTitle}RuleEngine.php`)
*   **Role**: The pure logic processor for game status.
*   **Responsibilities**:
    *   Implements `GameWinEvaluatorInterface`.
    *   Analyzes a `GameState` to determine if a Win, Loss, or Draw has occurred.
    *   Returns `GameOutcome` objects.
    *   Contains complex validation logic that spans multiple turns or board states.

### 4. The Reporter (`{GameTitle}Reporter.php`)
*   **Role**: The storyteller and analyst.
*   **Responsibilities**:
    *   Implements `GameReportingInterface`.
    *   Formats game events into human-readable logs.
    *   Generates analytics data (e.g., "Average moves per game", "Piece capture rate").
    *   Decouples logging/analytics from the core game logic.

### 5. State DTOs (`GameState.php`, `PlayerState.php`)
*   **Role**: Immutable (or effectively immutable) data carriers.
*   **Responsibilities**:
    *   Hold the current state of the board, scores, and players.
    *   Contain **no business logic** (only getters, setters, and serialization methods).
    *   Must be serializable to JSON for database storage.

### 6. Actions & Handlers
*   **Actions (`Actions/`)**: Simple DTOs representing a player's intent (e.g., `MovePiece`).
*   **Handlers (`Handlers/`)**: The logic that executes an action.
    *   Validates the action against the current state.
    *   Mutates the `GameState` (returns a new instance).
    *   Returns a `ValidationResult`.

### 7. Modes (`Modes/`)
*   **Role**: The entry points for different variations of the game.
*   **Responsibilities**:
    *   Extend the `Base{GameTitle}` class.
    *   Inject specific `Rules` and `RuleEngine` implementations into the Base class.
    *   Example: `SpeedMode` might use `StandardRules` but override the time limit.

## The Game Engine (`App/GameEngine`)

The core Game Engine provides the infrastructure that supports these game implementations.

*   **Interfaces**: Defines the contracts (`GameTitleContract`, `GameConfigInterface`, `GameWinEvaluatorInterface`) that ensure all games behave consistently.
*   **Kernel**: Manages the game loop, turn transitions, and persistence.
*   **Shared Actions**: Common actions like `MovePiece`, `PlacePiece`, `DrawCard` are defined globally in `App/GameEngine/Actions` to prevent code duplication. Games should use these whenever possible.

## Future Expansions

This structure allows for easy expansion in several directions:

1.  **New Game Modes**: Create a new class in `Modes/` that extends `Base{GameTitle}` and injects a different `Rules` configuration (e.g., "Chaos Mode" with different piece movement).
2.  **Rule Variations**: Create a new `Rules` class (e.g., `FrenchCheckersRules.php`) and swap it in via a Mode.
3.  **AI Agents**: The `GameState` DTOs provide a clean, standardized data structure that is easy to feed into AI models or heuristic algorithms.
4.  **Frontend Independence**: Since all logic and text descriptions are encapsulated in the backend, the frontend can be generic, rendering the board based on the `GameState` and displaying rules based on `{GameTitle}Rules`.
