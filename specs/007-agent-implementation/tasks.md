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

- [X] T001-T003 Verified environment: Laravel queue system, PostgreSQL, and existing models (User, Game, Agent)
- [X] T004-T006 Verified database migrations for `users` and `agents` tables (already exist with proper schema)
- [X] T007 Agent model already exists at `app/Models/Auth/Agent.php` with `user()` relationship
- [X] T008 User model already has `agent()` relationship and `isAgent()` method at `app/Models/Auth/User.php`
- [X] T009 [P] Created `AgentFactory` at `database/factories/AgentFactory.php`
- [X] T010 [P] Created `AgentContract` interface at `app/Interfaces/AgentContract.php`

**Checkpoint**: Foundation ready. User story implementation can now begin.

---

## Phase 2: User Story 1 - Human vs. Agent Quickplay (Priority: P1) 🎯 MVP

**Goal**: A human player can be matched with an AI agent in Quickplay and complete a game.

**Independent Test**: A single user enters Quickplay, is matched with an agent after 15 seconds, and the agent takes its turns with a human-like delay.

### Tests for User Story 1 (Test-First)

- [X] T011 [P] [US1] Created unit test for `AgentSchedulingService` in `tests/Unit/Agents/AgentSchedulingServiceTest.php`
- [X] T012 [P] [US1] Created unit test for `AgentService` in `tests/Unit/Agents/AgentServiceTest.php`
- [X] T013 [US1] Created feature test in `tests/Feature/Agents/QuickplayMatchmakingTest.php`

### Implementation for User Story 1

- [X] T014 [P] [US1] Created `AgentSchedulingService` in `app/Services/Agents/AgentSchedulingService.php`
- [X] T015 [P] [US1] Created `AgentService` in `app/Services/Agents/AgentService.php`
- [X] T016 [P] [US1] Created `CalculateAgentAction` job in `app/Jobs/CalculateAgentAction.php` with sleep() logic
- [X] T017 [P] [US1] Created `RandomLogic` AI implementation at `app/Agents/Logic/RandomLogic.php`
- [X] T018 [P] [US1] Created `MinimaxLogic` AI implementation at `app/Agents/Logic/MinimaxLogic.php`
- [X] T019 [P] [US1] Created `HeuristicLogic` AI implementation at `app/Agents/Logic/HeuristicLogic.php`
- [X] T020 [US1] Modified Quickplay logic in `app/Jobs/ProcessQuickplayQueue.php` to call `AgentSchedulingService` after 30-second timeout
- [X] T021 [US1] Modified game engine in `app/Http/Controllers/Api/V1/GameActionController.php` to call `AgentService::performAction()` when it's an agent's turn
- [X] T022 [US1] Created database seeder in `database/seeders/AgentSeeder.php` to populate initial agents

**Checkpoint**: User Story 1 is functional and testable.

---

## Phase 3: User Story 3 - Agent Adheres to Schedule (Priority: P3)

**Goal**: The matchmaking system respects the `available_hour_est` setting for each agent.

**Independent Test**: An agent configured for a specific hour is only matched during that hour.

### Tests for User Story 3 (Test-First)

- [ ] T023 [US3] Update the unit test in `tests/Unit/Agents/AgentSchedulingServiceTest.php` to include scenarios for time-based availability, mocking the current time to test inside and outside the available hour.

- [ ] T024 [US3] Agents that have the availability of null should only be chosen last if all the agents during that hour slot are not available.

### Implementation for User Story 3

- [ ] T025 [US3] Update the agent-finding logic in `AgentSchedulingService` to filter agents based on their `available_hour_est` against the current time in the `America/New_York` timezone.

**Checkpoint**: All user stories are implemented and tested. The feature is complete.
