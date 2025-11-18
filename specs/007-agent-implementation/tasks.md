# Tasks: Agent Implementation

**Input**: Design documents from `/specs/007-agent-implementation/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

---

## Phase 1: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented.


- [ ] T005 [P] Create the `AgentFactory` at `database/factories/AgentFactory.php`.
- [ ] T006 [P] Create the `AgentContract` interface at `app/Interfaces/AgentContract.php`.

**Checkpoint**: Foundation ready. User story implementation can now begin.

---

## Phase 2: User Story 1 - Human vs. Agent Quickplay (Priority: P1) 🎯 MVP

**Goal**: A human player can be matched with an AI agent in Quickplay and complete a game.

**Independent Test**: A single user enters Quickplay, is matched with an agent after 15 seconds, and the agent takes its turns with a human-like delay.

### Tests for User Story 1 (Test-First)

- [ ] T008 [P] [US1] Create a unit test for `AgentSchedulingService` in `tests/Unit/Agents/AgentSchedulingServiceTest.php` to verify it can find available, compatible, and non-busy agents.
- [ ] T009 [P] [US1] Create a unit test for `AgentService` in `tests/Unit/Agents/AgentServiceTest.php` to ensure it dispatches the `CalculateAgentAction` job correctly.
- [ ] T010 [US1] Create a feature test in `tests/Feature/Agents/QuickplayMatchmakingTest.php` to simulate a player waiting and being matched with an agent.

### Implementation for User Story 1

- [ ] T011 [P] [US1] Create the `AgentSchedulingService` in `app/Services/Agents/AgentSchedulingService.php`.
- [ ] T012 [P] [US1] Create the `AgentService` in `app/Services/Agents/AgentService.php`.
- [ ] T013 [P] [US1] Create the `CalculateAgentAction` job in `app/Jobs/CalculateAgentAction.php`. This job will contain the `sleep()` logic.
- [ ] T014 [P] [US1] Create a basic AI implementation, `RandomLogic`, at `app/Agents/Logic/RandomLogic.php` that implements `AgentContract`.
- [ ] T015 [P] [US1] Create the `MinimaxLogic` AI implementation at `app/Agents/Logic/MinimaxLogic.php`.
- [ ] T016 [P] [US1] Create the `HeuristicLogic` AI implementation at `app/Agents/Logic/HeuristicLogic.php`.
- [ ] T017 [US1] Modify the existing Quickplay logic to call `AgentSchedulingService` after the 15-second timeout for finding a human player.
- [ ] T018 [US1] Modify the game engine logic to call `AgentService::performAction()` when it is an agent's turn.

**Checkpoint**: User Story 1 is functional and testable.

---

## Phase 3: User Story 2 - Administrator Configures Agents (Priority: P2)

**Goal**: An administrator can create, view, update, and delete agent configurations.

**Independent Test**: An admin can create a new agent via an API endpoint, and that agent becomes available for matchmaking.

### Tests for User Story 2 (Test-First)

- [ ] T019 [US2] Create a feature test in `tests/Feature/Admin/AgentManagementTest.php` to cover the CRUD API endpoints for agents, including validation rules.

### Implementation for User Story 2

- [ ] T020 [P] [US2] Create the `AgentController` for admin functions at `app/Http/Controllers/Admin/AgentController.php`.
- [ ] T021 [US2] Add the new resource routes for agent management to `routes/api.php`, protected by appropriate admin middleware.
- [ ] T022 [US2] Implement the CRUD methods in `AgentController` (index, store, show, update, destroy).
- [ ] T023 [US2] Implement validation logic for creating and updating agents to enforce rules from `data-model.md`.

**Checkpoint**: User Story 2 is functional and testable.

---

## Phase 4: User Story 3 - Agent Adheres to Schedule (Priority: P3)

**Goal**: The matchmaking system respects the `available_hour_est` setting for each agent.

**Independent Test**: An agent configured for a specific hour is only matched during that hour.

### Tests for User Story 3 (Test-First)

- [ ] T024 [US3] Update the unit test in `tests/Unit/Agents/AgentSchedulingServiceTest.php` to include scenarios for time-based availability, mocking the current time to test inside and outside the available hour.

### Implementation for User Story 3

- [ ] T025 [US3] Update the agent-finding logic in `AgentSchedulingService` to filter agents based on their `available_hour_est` against the current time in the `America/New_York` timezone.

**Checkpoint**: All user stories are implemented and tested. The feature is complete.
