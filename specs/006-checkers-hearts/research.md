# Research: Extensible Game Framework

**Date**: 2025-11-17
**Feature**: [Add Checkers and Hearts Game Titles](../spec.md)

This document outlines the architectural decisions for creating a flexible game framework capable of supporting various genres.

## 1. Core Game Contracts

### Decision
We will establish a set of core PHP interfaces (`Contracts`) that every game title and its components must implement. This ensures a consistent API for the core application to interact with any game, regardless of its genre.

- **`GameTitleContract`**: The main entry point for a game like "Checkers". It will be responsible for creating the initial game state, providing game rules, and identifying its available modes.
- **`GameModeContract`**: Defines a specific ruleset for a game (e.g., "Standard Checkers"). It will contain the core logic for legal moves, win conditions, and state transitions.
- **`ActionContract`**: Represents a player's action (e.g., `MovePiece`, `PlayCard`). It will contain data about the action and be processed by the `GameMode`.

### Rationale
Using contracts decouples the application's core logic from the specific implementation of any single game. This allows new games to be added without modifying the central game management system. It forces a "convention over configuration" approach, making the system predictable.

### Alternatives Considered
- **Base Classes Only**: Relying solely on base classes (like `BaseBoardGameTitle`) would tightly couple the framework to specific game types. If a new genre didn't fit the base class model, it would require significant refactoring. Contracts provide a more flexible layer of abstraction.

## 2. Genre-Specific Base Classes

### Decision
We will create abstract base classes for different game genres that implement the core contracts. This provides shared functionality for games within the same genre while still adhering to the common interface.

- **`BaseBoardGameTitle` (Existing)**: Will be used for Checkers. It can provide helper methods for grid-based logic.
- **`BaseCardGameTitle` (New)**: Will be created for Hearts. It will provide helper methods for card game logic, such as deck creation, shuffling, and dealing.

### Rationale
This approach provides the best of both worlds: the flexibility of contracts and the convenience of shared code for common game types. Adding a new card game like "Spades" would be simple because it could extend `BaseCardGameTitle` and inherit its deck management features.

## 3. State Management for Round-Based Games

### Decision
The `GameState` object will be enhanced to support concepts required for round-based games like Hearts.

- **`GamePhase` Enum**: The existing `GamePhase` enum will be expanded to include phases specific to card games, such as `DEALING`, `PASSING`, `TRICK_IN_PROGRESS`, and `ROUND_COMPLETE`.
- **Round & Score Tracking**: The `GameState` for Hearts will include properties like `roundNumber` and `scores`. The `PlayerState` will hold a player's overall score across rounds.
- **Immutable State**: The principle of immutable state will be strictly enforced. Every action (e.g., `PlayCard`) will result in a *new* `GameState` object, preserving game history and simplifying debugging.

### Rationale
By modeling the game's entire lifecycle within the `GamePhase` enum and `GameState`, we create a predictable and testable state machine. The `GameTitle` acts as the engine that transitions the game from one state to the next based on player actions, but the state itself remains a simple, immutable data object. This is crucial for features like game replays and for recovering game state.
