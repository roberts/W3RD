# Task Plan: Validate Four

This document breaks down the implementation of the "Validate Four" feature into executable tasks, organized by priority.

## Phase 1: Setup

- [ ] T001 Update `composer.json` to add the `App\Games\` namespace to the PSR-4 autoloader in `composer.json`
- [ ] T002 Run `composer dump-autoload` to apply the autoloader changes
- [ ] T003 Create the initial directory structure: `app/Games/ValidateFour/Actions` and `app/Games/ValidateFour/Modes`

## Phase 2: Foundational Components

These tasks create the core building blocks required for all game modes.

- [ ] T004 Create the global game mode interface in `app/Interfaces/GameModeStrategy.php`
- [ ] T005 Create the base `ValidateFourGameState` class in `app/Games/ValidateFour/ValidateFourGameState.php`
- [ ] T006 Create the `AbstractValidateFourMode` class in `app/Games/ValidateFour/AbstractValidateFourMode.php`
- [ ] T007 [P] Create the `DropDisc` Action DTO in `app/Games/ValidateFour/Actions/DropDisc.php`
- [ ] T008 [P] Create the `PopOut` Action DTO in `app/Games/ValidateFour/Actions/PopOut.php`
- [ ] T009 Create the base rules file in `app/Games/ValidateFour/rules.php`
- [ ] T010 Create the `GameRulesController` and API route to serve the rules from `app/Games/{gameTitle}/rules.php`

## Phase 3: User Story 1 - Standard Mode

**Goal**: As a player, I want to play a Standard Mode game of Validate Four on a 7x6 grid.
**Test Criteria**: A user can create and complete a full game of Standard Mode, with win/loss/draw states correctly identified.

- [ ] T011 [US1] Create the `StandardMode` class in `app/Games/ValidateFour/Modes/StandardMode.php`
- [ ] T012 [US1] Implement constructor in `ValidateFourGameState` to initialize the board for Standard Mode (7x6, connect 4) in `app/Games/ValidateFour/ValidateFourGameState.php`
- [ ] T013 [US1] Implement `drop_disc` validation and application logic within `AbstractValidateFourMode` in `app/Games/ValidateFour/AbstractValidateFourMode.php`
- [ ] T014 [US1] Implement win condition logic (horizontal, vertical, diagonal) for a 4-disc connection in `AbstractValidateFourMode` in `app/Games/ValidateFour/AbstractValidateFourMode.php`
- [ ] T015 [US1] Implement draw condition logic in `AbstractValidateFourMode` in `app/Games/ValidateFour/AbstractValidateFourMode.php`
- [ ] T016 [US1] Implement the main game action endpoint to process `drop_disc` actions for Standard Mode

## Phase 4: User Story 2 - Pop Out Mode

**Goal**: As a player, I want to play a Pop Out Mode game.
**Test Criteria**: A user can successfully use the "pop out" action, and the game state updates correctly. The action is rejected if used on an opponent's piece.

- [ ] T017 [P] [US2] Create the `PopOutMode` class in `app/Games/ValidateFour/Modes/PopOutMode.php`
- [ ] T018 [P] [US2] Create the mode-specific rules file in `app/Games/ValidateFour/Modes/PopOutMode/rules.php`
- [ ] T019 [US2] Implement `pop_out` validation logic in `PopOutMode.php` to ensure a player can only pop their own disc.
- [ ] T020 [US2] Implement `pop_out` application logic in `PopOutMode.php` to remove the bottom disc and shift the column down.

## Phase 5: User Story 3 - 8x7 Mode

**Goal**: As a player, I want to play an 8x7 Mode on a larger 8x7 grid.
**Test Criteria**: A game can be created with an 8x7 board, and win/draw conditions function correctly on the larger grid.

- [ ] T021 [P] [US3] Create the `EightBySevenMode` class in `app/Games/ValidateFour/Modes/EightBySevenMode.php`
- [ ] T022 [P] [US3] Create the mode-specific rules file in `app/Games/ValidateFour/Modes/EightBySevenMode/rules.php`

## Phase 6: User Story 4 - 9x6 Mode

**Goal**: As a player, I want to play a 9x6 Mode on a wider 9x6 grid.
**Test Criteria**: A game can be created with a 9x6 board, and win/draw conditions function correctly on the wider grid.

- [ ] T023 [P] [US4] Create the `NineBySixMode` class in `app/Games/ValidateFour/Modes/NineBySixMode.php`
- [ ] T024 [P] [US4] Create the mode-specific rules file in `app/Games/ValidateFour/Modes/NineBySixMode/rules.php`

## Phase 7: User Story 5 - Five Mode

**Goal**: As a player, I want to play a Five Mode game where the goal is to connect five discs on a 9x6 grid.
**Test Criteria**: A game can be created with a 9x6 board and a `connect_length` of 5. A win is only triggered by a line of 5 discs.

- [ ] T025 [P] [US5] Create the `FiveMode` class in `app/Games/ValidateFour/Modes/FiveMode.php`
- [ ] T026 [P] [US5] Create the mode-specific rules file in `app/Games/ValidateFour/Modes/FiveMode/rules.php`

## Phase 8: Polish & Finalization

- [ ] T027 Update the `config/games.php` file (if created) to map all new modes to their respective classes.
- [ ] T028 Write feature tests to cover the API endpoints for game creation and action submission for each mode.

## Dependencies

- **US2, US3, US4, US5** depend on the completion of **US1** and the foundational logic in `AbstractValidateFourMode`.
- The mode-specific tasks (e.g., T017, T021, T023, T025) can be worked on in parallel after Phase 3 is complete.

## Implementation Strategy

The feature will be delivered incrementally. The Minimum Viable Product (MVP) consists of completing all tasks up to and including **Phase 3 (User Story 1)**. This will provide a fully functional standard game. Subsequent modes can be added in any order as separate enhancements.
