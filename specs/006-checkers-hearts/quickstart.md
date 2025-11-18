# Quickstart: Adding a New Game

**Date**: 2025-11-17
**Feature**: [Add Checkers and Hearts Game Titles](../spec.md)

This guide provides instructions for adding a new game to the platform, using the extensible framework. We will use "Tic-Tac-Toe" as an example.

### Step 1: Create the Game Directory

Create a new directory for your game inside `app/Games`.

```bash
mkdir app/Games/TicTacToe
mkdir app/Games/TicTacToe/Modes
```

### Step 2: Create the `GameTitle` Class

This class is the main entry point for your game.

**File**: `app/Games/TicTacToe/TicTacToeTitle.php`

```php
<?php

namespace App\Games\TicTacToe;

use App\Games\BaseBoardGameTitle;
use App\Games\Contracts\GameTitleContract;

class TicTacToeTitle extends BaseBoardGameTitle implements GameTitleContract
{
    public static function getIdentifier(): string
    {
        return 'tic-tac-toe';
    }

    public function createInitialState(string ...$playerUlids): object
    {
        // Logic to create the starting GameState for Tic-Tac-Toe
    }

    public static function getRules(): array
    {
        // Return a structured array of rules
    }
    
    public function getAvailableModes(): array
    {
        return [
            Modes\Standard::class,
        ];
    }
}
```

### Step 3: Create the `GameMode` Class

This class contains the specific rules for the "Standard" mode of your game.

**File**: `app/Games/TicTacToe/Modes/Standard.php`

```php
<?php

namespace App\Games\TicTacToe\Modes;

use App\Games\Contracts\GameModeContract;
use App\Games\Contracts\ActionContract;
use App\Games\TicTacToe\GameState;

class Standard implements GameModeContract
{
    public static function getIdentifier(): string
    {
        return 'standard';
    }

    public function processAction(object $gameState, ActionContract $action): object
    {
        // Validate and apply the action, return a new GameState
    }

    public function isActionValid(object $gameState, ActionContract $action): bool
    {
        // Check if a move is legal
    }

    public function checkForWinner(object $gameState): ?string
    {
        // Check for a win condition and return the winner's ULID or null
    }
}
```

### Step 4: Create the `GameState` and `PlayerState`

These are simple data objects.

**File**: `app/Games/TicTacToe/GameState.php`
```php
<?php

namespace App\Games\TicTacToe;

use App\Games\BaseGameState;

final class GameState extends BaseGameState
{
    // public readonly array $board;
    // ... other properties
}
```

### Step 5: Register the Game

Add your new `GameTitle` class to the `protocol.php` config file.

```php
// in config/protocol.php
'game_titles' => [
    // ... other games
    \App\Games\TicTacToe\TicTacToeTitle::class,
],
```
