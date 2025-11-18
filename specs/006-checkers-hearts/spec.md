# Feature Specification: Add Checkers and Hearts Game Titles

**Feature Branch**: `006-checkers-hearts`  
**Created**: 2025-11-17  
**Status**: Draft  
**Input**: User description: "adding checkers & hearts as game titles with standard modes"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Play a Game of Checkers (Priority: P1)

As a player, I want to be able to start and play a complete game of Checkers against another player according to standard rules, so that I can enjoy a classic board game on the platform.

**Why this priority**: This is a core deliverable of the feature and provides immediate value by adding a well-known game to the platform's library.

**Independent Test**: This can be tested by two players joining a game lobby, starting a Checkers match, playing through to a win/loss/draw condition, and verifying the game state and outcome are correct.

**Acceptance Scenarios**:

1.  **Given** two players are in a game lobby, **When** they start a "Checkers - Standard" match, **Then** a new game is created with an 8x8 board set up for the start of a game.
2.  **Given** it is a player's turn in a Checkers game, **When** they make a valid move, **Then** the board state is updated and the turn passes to the opponent.
3.  **Given** a player has a valid jump available, **When** they attempt to make a non-jump move, **Then** the move is rejected.
4.  **Given** a player's piece reaches the opponent's back row, **When** the turn ends, **Then** that piece is promoted to a King.
5.  **Given** a player captures all of the opponent's pieces, **When** the final capture is made, **Then** the game ends and the player is declared the winner.

---

### User Story 2 - Play a Game of Hearts (Priority: P2)

As a player, I want to be able to play a complete 4-player game of Hearts according to standard rules, so that I can enjoy a classic card game on the platform.

**Why this priority**: This adds a card game to the platform, diversifying the game offerings and appealing to a different player segment.

**Independent Test**: This can be tested by four players starting a Hearts match, playing through multiple rounds of trick-taking, and ensuring scores are calculated correctly until a winner is determined.

**Acceptance Scenarios**:

1.  **Given** four players are in a game lobby, **When** they start a "Hearts - Standard" match, **Then** a new game is created, cards are dealt, and the game enters the card passing phase.
2.  **Given** it is the first round, **When** players select three cards to pass, **Then** the cards are passed to the player on their left.
3.  **Given** a player leads a trick, **When** other players play their cards in turn, **Then** the winner of the trick is determined, they collect the cards, and lead the next trick.
4.  **Given** a player has no cards of the lead suit, **When** they play a Heart or the Queen of Spades, **Then** the trick is broken if it's the first time Hearts have been played.
5.  **Given** a round ends, **When** scores are calculated, **Then** points are correctly assigned for any Hearts or the Queen of Spades taken, and the game continues until a player reaches 100 points.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST provide a "Checkers" `GameTitle` with a "Standard" `GameMode`.
- **FR-002**: The Checkers game MUST be for 2 players on an 8x8 board.
- **FR-003**: The Checkers game logic MUST enforce standard rules, including simple piece movement, forced jumps, and promotion to Kings.
- **FR-004**: The system MUST provide a "Hearts" `GameTitle` with a "Standard" `GameMode`.
- **FR-005**: The Hearts game MUST be for 4 players.
- **FR-006**: The Hearts game logic MUST enforce standard rules, including card passing rounds (left, right, across, hold), trick-taking, point calculation (1 point per Heart, 13 for Queen of Spades), and "Shooting the Moon".
- **FR-007**: The system MUST be able to determine a win, loss, or draw condition for both games and update the game status accordingly.

### Key Entities

- **GameTitle**: Represents a playable game (e.g., Checkers, Hearts). It defines the game's identity and available modes.
- **GameMode**: Represents a specific ruleset for a `GameTitle` (e.g., Standard). It contains the core logic for how the game is played, including setup, legal moves, and win conditions.
- **GameState**: An immutable object representing the complete state of a single game instance at a point in time (e.g., board layout for Checkers, player hands and scores for Hearts).
- **PlayerState**: An immutable object representing a player's state within a game (e.g., their color in Checkers, their score in Hearts).
- **Action**: Represents a player's move or decision within a game (e.g., `MovePiece`, `PlayCard`).

## Assumptions

- **AS-001**: The scope of this feature is limited to the backend game logic and state management. UI/frontend implementation is not included.
- **AS-002**: "Standard" rules for both Checkers and Hearts will be based on the most commonly accepted North American rule sets.
- **AS-003**: The existing `BaseGameTitle` and `BaseGameState` structures will be extended to support card games as well as board games.

## Success Criteria

- **SC-001**: Players can successfully create, play, and complete a game of Checkers from start to finish without encountering logic errors.
- **SC-002**: Players can successfully create, play, and complete a game of Hearts from start to finish without encountering logic errors.
- **SC-003**: All game rules for both Checkers (Standard) and Hearts (Standard) are correctly implemented and verifiable through automated tests and manual play-testing.
- **SC-004**: The new game titles can be discovered and selected when creating a new game match.
- **SC-005**: The framework for adding these games is extensible, allowing for future game titles to be added with minimal refactoring of the core system.
