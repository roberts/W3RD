# Game Logic & Rules Architecture

This document outlines the architectural standards for implementing game logic, validation, and rules for new `GameTitles` and their `Modes`. The goal is to create a structure that is scalable, maintainable, and adheres to the DRY (Don't Repeat Yourself) principle.

## Core Philosophy

The architecture is built on these key concepts:
1.  **Co-location:** All logic, state, actions, and rules for a single game title are stored together in a dedicated directory.
2.  **Separation of Concerns:** Logic is separated into distinct layers, from a universal contract down to mode-specific variations.
3.  **Strong Typing:** Generic arrays are avoided in favor of dedicated Data Transfer Objects (DTOs) for game state and player actions to improve clarity and reduce errors.
4.  **Configuration over Convention:** A central configuration file is used to map games to their logic classes, decoupling the system and making it more flexible.

---

## Directory Structure

All core game logic is organized by business domain rather than technical type. This aligns with Domain-Driven Design (DDD) principles, making the codebase more intuitive and scalable. The primary domain for all game logic resides in `app/Games/`.

To enable this, the `composer.json` file must be updated to autoload this directory:
```json
// composer.json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "App\\Games\\": "app/Games/",
        // ...
    }
},
```
After updating, run `composer dump-autoload`.

The following structure for a "Chess" game should be used as a template:

```
app/Games/Chess/
├── Modes/
│   ├── StandardMode.php
│   └── BlitzMode.php
│
├── Actions/
│   ├── Move.php
│   └── Promote.php
│
├── AbstractChessMode.php
├── ChessGameState.php
└── rules.php
```

### File Descriptions

*   **`Modes/`**: Contains the concrete logic classes for each specific game mode.
*   **`Actions/`**: Contains the Data Transfer Objects (DTOs) that represent a specific player action (e.g., a move, a promotion).
*   **`AbstractChessMode.php`**: The abstract base class for this game title. It contains all shared logic, validation, and helper methods common to all modes of Chess.
*   **`ChessGameState.php`**: A dedicated class that provides a strongly-typed representation of the `game_state` JSON from the database.
*   **`rules.php`**: A PHP array containing the human-readable descriptions of the game's rules and its modes, used to serve information to the client via an API. The filename is lowercase as it returns a configuration array, not a class.

---

## The Three Layers of Logic

The core of the game engine is a three-layer system based on the Strategy design pattern.

### 1. The Global Interface (`GameModeContract`)

This is the universal contract for all game modes in the entire application. It guarantees that the system can interact with any game's rules in a consistent way.

```php
// app/Interfaces/GameModeContract.php
interface GameModeContract
{
    public function validateAction(object $gameState, object $action): bool;
    public function applyAction(object $gameState, object $action): object;
    public function checkEndCondition(object $gameState): ?Player;
}
```

### 2. The Game-Specific Abstract Class (`AbstractChessMode`)

This is the heart of our DRY implementation. This class `implements GameModeContract` and contains all the shared logic for a specific game title.

*   **Responsibilities:**
    *   Implement methods for validating piece movements (e.g., `isRookMoveValid()`).
    *   Contain logic for core game concepts (e.g., `isKingInCheck()`).
    *   Provide a base `validateAction()` method that calls these shared helpers.
*   **Benefit:** If a bug is found in how a piece moves, it is fixed once in this file, and the fix is automatically applied to all modes of that game.

### 3. Concrete Mode Classes (`StandardMode`, `BlitzMode`)

These classes `extend` the game's abstract class (e.g., `class StandardMode extends AbstractChessMode`).

*   **Responsibilities:**
    *   They inherit all the common logic for free.
    *   They only implement or **override** methods that are unique to that specific mode. For example, `BlitzMode` would override `checkEndCondition()` to add a check for the game timer.

---

## Handling Game State and Actions

### Game State (`ChessGameState.php`)

To avoid dealing with messy arrays, we use dedicated state objects. This class takes the raw `game_state` array from the database in its constructor and maps it to strongly-typed properties. The rule logic then operates on this clean object, providing autocompletion and preventing bugs.

### Player Actions (`Actions/Move.php`)

Similarly, we use Data Transfer Objects (DTOs) to represent player actions.

*   **Naming:** Action DTOs are named after what they *are* (e.g., `Move`, `Promote`, `Castle`), not what they do.
*   **Validation:** The DTO's constructor is responsible for validating the raw input from the API. This ensures that malformed data fails fast before it ever reaches the complex game logic.
*   **Type Hinting:** The `validateAction` method in the rule engine can then require a specific Action DTO, making the code self-documenting and robust.

```php
// Example of a clean method signature
public function validateAction(ChessGameState $state, Move $action): bool
```

---

## Serving Human-Readable Rules

To provide rule information to clients via an API, we store descriptions in simple PHP arrays, separate from the game logic.

### Base and Mode-Specific Rules

*   **Base Rules:** The `app/Games/Chess/rules.php` file contains the title, description, and rules common to all modes of Chess.
*   **Mode Variations:** An optional, smaller `rules.php` file can be placed inside a mode's directory (e.g., `Modes/Blitz/rules.php`) to define only the rules that are different or additional for that mode.

An API controller is responsible for loading the base rule array, then loading the mode-specific array and merging them together to produce a complete set of rules for the client.
