# Data Model: Checkers & Hearts

**Date**: 2025-11-17
**Feature**: [Add Checkers and Hearts Game Titles](../spec.md)

This document defines the data structures for the new game entities, based on the principle of immutable state.

## Core Entities

These entities are conceptual and will be implemented as PHP classes, many of which will be `Spatie\LaravelData\Data` objects for easy serialization.

### 1. `GameTitle`

Represents a game like "Checkers" or "Hearts".

- **Implementation**: A PHP class that implements `GameTitleContract`.
- **Examples**: `app/GameTitles/Checkers/CheckersTitle.php`, `app/GameTitles/Hearts/HeartsTitle.php`.

### 2. `GameMode`

Represents a specific ruleset, like "Standard".

- **Implementation**: A PHP class that implements `GameModeContract`.
- **Examples**: `app/GameTitles/Checkers/Modes/Standard.php`, `app/GameTitles/Hearts/Modes/Standard.php`.

### 3. `GameState`

An immutable data object holding the entire state of a game at one point in time.

#### Checkers `GameState`
- `players`: `array<string, PlayerState>` - Maps player ULID to their state.
- `currentPlayerUlid`: `?string` - The player whose turn it is.
- `phase`: `GamePhase` - e.g., `IN_PROGRESS`, `GAME_OVER`.
- `status`: `GameStatus` - e.g., `ACTIVE`, `COMPLETED`.
- `board`: `array<int, array<int, ?array>>` - 8x8 grid. A cell contains `null` or an array like `['player' => 'ulid', 'king' => false]`.
- `winnerUlid`: `?string` - ULID of the winning player.

#### Hearts `GameState`
- `players`: `array<string, PlayerState>` - Maps player ULID to their state.
- `currentPlayerUlid`: `?string` - The player leading the trick or playing next.
- `phase`: `GamePhase` - e.g., `PASSING`, `TRICK_IN_PROGRESS`, `ROUND_COMPLETE`.
- `status`: `GameStatus` - e.g., `ACTIVE`, `COMPLETED`.
- `roundNumber`: `int` - The current round number (1, 2, 3...).
- `hands`: `array<string, array<string>>` - Maps player ULID to their hand of cards (e.g., `['H2', 'CQ']`).
- `currentTrick`: `array<string, string>` - Maps player ULID to the card they played in the current trick.
- `trickLeaderUlid`: `?string` - The player who leads the current trick.
- `heartsBroken`: `bool` - Whether hearts have been played yet.
- `winnerUlid`: `?string` - ULID of the overall game winner (player with lowest score when another hits 100).

### 4. `PlayerState`

An immutable data object holding a player's state within a game.

#### Checkers `PlayerState`
- `ulid`: `string` - Player's ULID.
- `color`: `string` - e.g., 'red' or 'black'.
- `piecesRemaining`: `int` - Count of pieces still on the board.

#### Hearts `PlayerState`
- `ulid`: `string` - Player's ULID.
- `position`: `int` - The player's fixed seat at the table (1-4).
- `score`: `int` - The player's total score across all rounds.
- `roundScore`: `int` - Points taken in the current round.

### 5. `Action`

A data object representing a player's move.

#### Checkers `MovePieceAction`
- `playerUlid`: `string`
- `from`: `array{row: int, col: int}`
- `to`: `array{row: int, col: int}`

#### Hearts `PlayCardAction`
- `playerUlid`: `string`
- `card`: `string` - e.g., 'SQ' for Queen of Spades.

#### Hearts `PassCardsAction`
- `playerUlid`: `string`
- `cards`: `array<string>` - The three cards being passed.
