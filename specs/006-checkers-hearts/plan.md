# Implementation Plan: Add Checkers and Hearts Game Titles

**Branch**: `006-checkers-hearts` | **Date**: 2025-11-17 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/006-checkers-hearts/spec.md`

## Summary

This plan outlines the implementation of a flexible and extensible game framework and adds two new games, Checkers and Hearts, with their standard modes. The core approach is to use a contract-based design, allowing new games of various genres to be added in the future with minimal effort. A new `BaseCardGameTitle` will be created to support card games, complementing the existing `BaseBoardGameTitle`.

## Technical Context

**Language/Version**: PHP 8.4
**Primary Dependencies**: Laravel Framework v12.10, Pest v4.1
**Storage**: PostgreSQL (via Eloquent ORM)
**Testing**: Pest
**Target Platform**: Linux (Web Server)
**Project Type**: Web Application (Backend)
**Performance Goals**: Standard web application performance; no specific metrics defined for this feature.
**Constraints**: The architecture must be extensible to support future game titles of different genres (board, card, dice, etc.).
**Scale/Scope**: This feature covers the backend implementation for two new games. The framework should be robust enough to handle dozens of future game titles.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Library-First**: **PASS**. The logic for each game (Checkers, Hearts) will be encapsulated within its own directory (`app/Games/Checkers`, `app/Games/Hearts`), making them self-contained modules.
- **CLI Interface**: **PASS**. While not a CLI-focused feature, new games will be manageable via existing Artisan commands for interacting with the application.
- **Test-First**: **PASS**. The implementation will follow a TDD approach. Tests will be written for game logic, state transitions, and rule enforcement before the features are implemented.
- **Integration Testing**: **PASS**. Integration tests will be created to ensure the new game titles integrate correctly with the core game management system.
- **Simplicity**: **PASS**. The design uses contracts and base classes to manage complexity, avoiding over-engineering while providing necessary flexibility.

## Project Structure

### Documentation (this feature)

```text
specs/006-checkers-hearts/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
│   ├── ActionContract.php
│   ├── GameModeContract.php
│   └── GameTitleContract.php
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
app/
├── Games/
│   ├── Contracts/
│   │   ├── ActionContract.php
│   │   ├── GameModeContract.php
│   │   └── GameTitleContract.php
│   ├── Checkers/
│   │   ├── Actions/
│   │   ├── Modes/
│   │   │   └── Standard.php
│   │   ├── CheckersTitle.php
│   │   ├── GameState.php
│   │   └── PlayerState.php
│   ├── Hearts/
│   │   ├── Actions/
│   │   ├── Modes/
│   │   │   └── Standard.php
│   │   ├── HeartsTitle.php
│   │   ├── GameState.php
│   │   └── PlayerState.php
│   ├── BaseCardGameTitle.php # New
│   └── ... # Existing files
└── ... # Other existing directories

tests/
├── Feature/
│   ├── Api/
│   │   └── Games/
│   │       ├── CheckersTest.php
│   │       └── HeartsTest.php
│   ├── Games/
│   │   ├── CheckersTest.php
│   │   └── HeartsTest.php
└── ... # Other existing directories
```

**Structure Decision**: The existing Laravel project structure will be used. New game logic will be added within the `app/Games` directory, with each game in its own subdirectory. This keeps the game logic organized and modular. New contracts will be placed in `app/Games/Contracts` to be accessible by all games.

## API Endpoint Testing

To ensure the games are fully playable via the API, a suite of Pest feature tests will be created. These tests will simulate a full game flow by making requests to the API endpoints, verifying the game state transitions, and asserting the final outcomes.

**Testing Strategy**:

-   **`tests/Feature/Api/Games/CheckersTest.php`**: This test will cover the full lifecycle of a Checkers game.
    -   It will create two users and authenticate them.
    -   It will simulate creating a new game match via a `POST` request to an endpoint like `/api/matches`.
    -   It will then send a series of `POST` requests to an endpoint like `/api/matches/{matchId}/actions` to simulate each player making moves.
    -   After each move, it will make a `GET` request to `/api/matches/{matchId}` to fetch the updated game state and assert that it is correct.
    -   Finally, it will assert that the game ends with the correct winner.

-   **`tests/Feature/Api/Games/HeartsTest.php`**: This test will cover the full lifecycle of a Hearts game.
    -   It will create four users and authenticate them.
    -   It will simulate creating a new game match.
    -   It will test the card passing phase.
    -   It will simulate multiple rounds of trick-taking.
    -   It will assert that scores are calculated correctly after each round.
    -   It will assert that the game ends when a player reaches the score limit.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| *N/A*     | -          | -                                   |
