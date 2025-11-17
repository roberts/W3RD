# Tasks: Lobby and Matchmaking System

**Feature**: Lobby and Matchmaking System
**Branch**: `003-lobby-and-matchmaking`

This document outlines the tasks required to implement the lobby and matchmaking system.

## Phase 1: Setup and Foundation

This phase focuses on creating the core database structure and models for the lobby system.

- [X] T001 Create `Lobby` model in `app/Models/Lobby.php`.
- [X] T002 Create `LobbyPlayer` model in `app/Models/LobbyPlayer.php`.
- [X] T003 Create `LobbyStatus` enum in `app/Enums/LobbyStatus.php`.
- [X] T004 Create `LobbyPlayerStatus` enum in `app/Enums/LobbyPlayerStatus.php`.
- [X] T005 Create migration for `lobbies` table in `database/migrations/`.
- [X] T006 Create migration for `lobby_players` table in `database/migrations/`.
- [X] T007 Create `LobbyFactory` in `database/factories/LobbyFactory.php`.
- [X] T008 Create `LobbyPlayerFactory` in `database/factories/LobbyPlayerFactory.php`.
- [X] T009 [P] Create `GameFound` event in `app/Events/GameFound.php`.
- [X] T010 [P] Create `LobbyInvitation` event in `app/Events/LobbyInvitation.php`.
- [X] T011 [P] Create `LobbyReadyCheck` event in `app/Events/LobbyReadyCheck.php`.

## Phase 2: User Story 1 - Public Matchmaking (Quickplay)

**Goal**: A player can join a public queue and be automatically matched with an opponent.

- [X] T012 [US1] Create `QuickplayController` in `app/Http/Controllers/Api/V1/QuickplayController.php`.
- [X] T013 [US1] Implement `POST /api/v1/games/quickplay` endpoint to join the queue.
- [X] T014 [US1] Implement `DELETE /api/v1/games/quickplay` endpoint to leave the queue.
- [X] T015 [US1] Implement `POST /api/v1/games/quickplay/accept` endpoint to confirm a match.
- [X] T016 [US1] Create `ProcessQuickplayQueue` job in `app/Jobs/ProcessQuickplayQueue.php`.
- [X] T017 [US1] Implement matchmaking logic in `ProcessQuickplayQueue` (human-to-human).
- [X] T018 [US1] Implement AI fallback logic in `ProcessQuickplayQueue`.
- [X] T019 [US1] Implement queue dodge penalty logic in `QuickplayController`.
- [X] T020 [US1] Add Quickplay routes to `routes/api.php`.
- [X] T021 [US1] Create `QuickplayTest` feature test in `tests/Feature/QuickplayTest.php`.
- [X] T022 [US1] Write tests for joining and leaving the queue.
- [X] T023 [US1] Write tests for match acceptance and declining.
- [X] T024 [US1] Write tests for the queue dodge penalty.

## Phase 3: User Story 2 - Lobby Creation and Management

**Goal**: A player can create and manage public and private game lobbies.

- [X] T025 [US2] Create `LobbyController` in `app/Http/Controllers/Api/V1/LobbyController.php`.
- [X] T026 [US2] Implement `POST /api/v1/games/lobbies` to create a lobby.
- [X] T027 [US2] Implement `GET /api/v1/games/lobbies` to list public lobbies.
- [X] T028 [US2] Implement `GET /api/v1/games/lobbies/{lobby_ulid}` to get lobby details.
- [X] T029 [US2] Implement `DELETE /api/v1/games/lobbies/{lobby_ulid}` to cancel a lobby.
- [X] T030 [US2] Implement `POST /api/v1/games/lobbies/{lobby_ulid}/ready-check` for the host.
- [X] T031 [US2] Add Lobby routes to `routes/api.php`.
- [X] T032 [US2] Create `LobbyTest` feature test in `tests/Feature/LobbyTest.php`.
- [X] T033 [US2] Write tests for creating public and private lobbies.
- [X] T034 [US2] Write tests for listing and viewing lobbies.
- [X] T035 [US2] Write tests for cancelling a lobby.

## Phase 4: User Story 3 - Lobby Participation

**Goal**: Players can be invited to, join, and leave lobbies.

- [X] T036 [US3] Create `LobbyPlayerController` in `app/Http/Controllers/Api/V1/LobbyPlayerController.php`.
- [X] T037 [US3] Implement `POST /api/v1/games/lobbies/{lobby_ulid}/players` to invite a player.
- [X] T038 [US3] Implement `PUT /api/v1/games/lobbies/{lobby_ulid}/players/{user_id}` to respond to an invitation.
- [X] T039 [US3] Implement `DELETE /api/v1/games/lobbies/{lobby_ulid}/players/{user_id}` to kick a player.
- [X] T040 [US3] Add Lobby Player routes to `routes/api.php`.
- [X] T041 [US3] Add tests for inviting, accepting, declining, and kicking players to `LobbyTest.php`.

## Phase 5: User Story 4 - Scheduled Lobbies

**Goal**: A host can schedule a lobby to start at a future time.

- [X] T042 [US4] Create `ProcessScheduledLobbies` job in `app/Jobs/ProcessScheduledLobbies.php`.
- [X] T043 [US4] Implement logic to start scheduled games that meet player criteria.
- [X] T044 [US4] Implement logic to cancel scheduled games that do not meet criteria.
- [X] T045 [US4] Register `ProcessScheduledLobbies` in the console kernel.
- [X] T046 [US4] Add tests for scheduled lobby creation and automatic start/cancellation to `LobbyTest.php`.

## Phase 6: Polish and Finalization

- [X] T047 [P] Review and refactor all new controllers and jobs for clarity and performance.
- [X] T048 [P] Ensure all new code adheres to project code style standards.
- [X] T049 [P] Update `docs/matchmaking.md` to reflect the final implementation of both Quickplay and Lobbies.

## Dependencies

- **US1 (Quickplay)** can be developed independently of other user stories.
- **US2 (Lobby Creation)** is a prerequisite for US3 and US4.
- **US3 (Lobby Participation)** depends on US2.
- **US4 (Scheduled Lobbies)** depends on US2.

## Implementation Strategy

The implementation will proceed in the order of the phases defined above. The Quickplay feature (US1) can be built in parallel with the foundational Lobby work (US2). Once lobby creation is complete, participation and scheduling features can be added. Each user story should be considered a deliverable slice of functionality.
