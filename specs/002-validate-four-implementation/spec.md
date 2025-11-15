# Feature Specification: Validate Four

**Version**: 1.0
**Status**: In Review
**Author**: GitHub Copilot

## 1. Overview

This document specifies the requirements for a new game titled "Validate Four," a variant of the classic Connect Four game. The feature includes a standard mode and several alternative modes with different board sizes, win conditions, and gameplay mechanics. The implementation will follow the architecture defined in `docs/logic.md`.

## 2. User Stories

| ID  | Priority | User Story                                                                                                                            |
| --- | -------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| US1 | P1       | As a player, I want to play a **Standard Mode** game of Validate Four on a 7x6 grid, so that I can experience the classic gameplay.      |
| US2 | P2       | As a player, I want to play a **Pop Out Mode** game, so that I can use the new mechanic of removing my own disc from the bottom of a column. |
| US3 | P3       | As a player, I want to play an **8x7 Mode** on a larger 8x7 grid, so that I can have a more complex game.                               |
| US4 | P3       | As a player, I want to play a **9x6 Mode** on a wider 9x6 grid, so that I can try different strategies.                                 |
| US5 | P3       | As a player, I want to play a **Five Mode** game where the goal is to connect five discs on a 9x6 grid, so that I can have a longer game. |

## 3. Functional Requirements

### FR1: Game Creation
- The system MUST allow a new game of "Validate Four" to be created.
- The game creation process MUST allow for the selection of one of the following modes:
  - Standard Mode (7x6 grid, connect 4)
  - Pop Out Mode (7x6 grid, connect 4)
  - 8x7 Mode (8x7 grid, connect 4)
  - 9x6 Mode (9x6 grid, connect 4)
  - Five Mode (9x6 grid, connect 5)
- The initial `game_state` MUST correctly reflect the `board_width`, `board_height`, and `connect_length` for the selected mode.

### FR2: Gameplay Actions
- Players MUST be able to perform a `drop_disc` action on their turn.
- The system MUST validate the `drop_disc` action, rejecting it if the selected column is full.
- In "Pop Out Mode," players MUST be able to perform a `pop_out` action on their turn.
- The system MUST validate the `pop_out` action, rejecting it if:
  - The chosen column does not contain any of the player's own discs.
  - The player attempts to pop a disc belonging to the opponent.
- The system MUST alternate turns between players correctly.

### FR3: Game State & Rules Engine
- The game engine MUST correctly apply the `drop_disc` action, placing the disc in the lowest available position of the chosen column.
- The game engine MUST correctly apply the `pop_out` action, removing the bottom-most disc and shifting all discs above it down by one position.
- The system MUST accurately detect a winning condition (a line of `connect_length` discs) horizontally, vertically, and diagonally.
- The system MUST accurately detect a draw condition (the board is full, and no winner has been determined).
- Upon a win or draw, the game `status` MUST be updated to "finished," and no further actions shall be accepted.

### FR4: API
- An API endpoint MUST be available to retrieve the rules for "Validate Four" and all its modes.
- An API endpoint MUST be available for players to submit their actions.

## 4. Success Criteria

- **Correctness**: 100% of valid moves are accepted and correctly update the game state. 100% of invalid moves are rejected with a clear error message.
- **Win/Draw Detection**: The system correctly identifies the game outcome (win, loss, or draw) in 100% of test cases.
- **Performance**: The API response time for submitting a valid game action is under 250ms.
- **Usability**: The rules returned by the API are clear and sufficient for a client application to render instructions for all game modes.

## 5. Assumptions

- Two players are required for each game.
- The user interface for game creation and gameplay will be handled by a separate client application consuming the API.
- Player authentication and identification are handled by the existing application structure.
