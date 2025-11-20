# Tasks: Production-Ready V1 API Structure

**Input**: Design documents from `/specs/008-api-structure/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: Tests are NOT explicitly requested in the feature specification. Tasks focus on implementation.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

All paths assume Laravel project structure at repository root:
- Controllers: `app/Http/Controllers/Api/V1/`
- Models: `app/Models/`
- Services: `app/Services/`
- DTOs: `app/DataTransferObjects/`
- Middleware: `app/Http/Middleware/`
- Routes: `routes/api.php`, `routes/channels.php`
- Migrations: `database/migrations/`
- Config: `config/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and configuration for API restructure

- [x] T001 Create namespace directory structure for 9 API namespaces in app/Http/Controllers/Api/V1/
- [x] T002 Configure Redis with 3 separate databases in config/database.php (default: 0, cache: 1, idempotency: 2)
- [x] T003 [P] Configure Laravel Reverb broadcasting connection in config/broadcasting.php
- [x] T004 [P] Add Reverb environment variables to .env.example (REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET, REVERB_HOST, REVERB_PORT, REVERB_SCHEME)
- [x] T005 [P] Update API routes file to use namespace grouping in routes/api.php

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T006 Create EnsureIdempotency middleware in app/Http/Middleware/EnsureIdempotency.php with Redis lock handling
- [x] T007 Update exception handler with correlation ID logging and standardized JSON error responses in app/Exceptions/Handler.php
- [x] T008 [P] Create BusinessRuleException class with factory methods in app/Exceptions/BusinessRuleException.php
- [x] T009 [P] Create MembershipPlan enum with Free/Pro/Elite tiers in app/Enums/MembershipPlan.php
- [x] T010 [P] Create base Data Transfer Object classes for each namespace in app/DataTransferObjects/
- [x] T011 ~~Create migration: add stripe_customer_id to users table~~ (Not needed - users already have stripe_id from Laravel Cashier)
- [x] T012 Add outcome fields to games table (final_scores, xp_awarded, rewards) - Added directly to database/migrations/2025_11_13_000007_create_games_table.php
- [x] T013 Add membership_plan to subscription_items table - Added directly to database/migrations/2025_11_17_035428_create_subscription_items_table.php
- [x] T014 Rename rematch_requests to proposals table - Renamed directly in database/migrations/2025_11_17_035314_create_proposals_table.php with all new fields (type, title_slug, mode_id, game_settings, responded_at)
- [x] T015 Create matchmaking_signals table in database/migrations/2025_11_20_174651_create_matchmaking_signals_table.php
- [x] T016 [P] Register idempotency middleware alias in bootstrap/app.php
- [x] T017 [P] Setup channel authorization patterns in routes/channels.php for games, lobbies, tournaments, leaderboards

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - System Health & Configuration Access (Priority: P1) 🎯 MVP

**Goal**: Platform operators and client applications can verify service availability and retrieve global configuration to ensure clients handle outages gracefully and maintain synchronized configuration.

**Independent Test**: Make unauthenticated requests to system endpoints and verify response codes and data structures without any game or user context.

### Implementation for User Story 1

- [x] T018 [P] [US1] Create HealthController with service status checks in app/Http/Controllers/Api/V1/System/HealthController.php
- [x] T019 [P] [US1] Create TimeController with authoritative server time endpoint in app/Http/Controllers/Api/V1/System/TimeController.php
- [x] T020 [P] [US1] Create ConfigController with platform configuration endpoint in app/Http/Controllers/Api/V1/System/ConfigController.php
- [x] T021 [P] [US1] Create WebhookController with provider event processing in app/Http/Controllers/Api/V1/Webhooks/WebhookController.php
- [x] T022 [US1] Create SystemHealthService with database, cache, queue, game engine checks in app/Services/SystemHealthService.php
- [x] T023 [US1] Add System namespace routes to routes/api.php (GET /v1/system/health, GET /v1/system/time, GET /v1/system/config)
- [x] T024 [US1] Add Webhooks namespace routes to routes/api.php (POST /v1/webhooks/{provider})
- [x] T025 [US1] Create HealthData DTO in app/DataTransferObjects/System/HealthData.php
- [x] T026 [US1] Create ConfigData DTO in app/DataTransferObjects/System/ConfigData.php
- [x] T027 [US1] Delete old StatusController from app/Http/Controllers/Api/V1/StatusController.php
- [x] T028 [US1] Delete old StripeWebhookController from app/Http/Controllers/Api/V1/StripeWebhookController.php

**Checkpoint**: System endpoints operational - health monitoring, time sync, and webhook processing functional

---

## Phase 4: User Story 2 - Game Library Discovery (Priority: P1)

**Goal**: Users and client applications can browse available games, view game metadata, access rule documentation, and cache static game assets before entering matchmaking or gameplay.

**Independent Test**: Query library endpoints without authentication and verify game metadata, rules, and asset definitions are returned correctly.

### Implementation for User Story 2

- [ ] T029 [P] [US2] Create GameLibraryController with index, show, entities methods in app/Http/Controllers/Api/V1/Library/GameLibraryController.php
- [ ] T030 [P] [US2] Move GameRulesController to namespace in app/Http/Controllers/Api/V1/Library/GameRulesController.php
- [ ] T031 [US2] Create GameLibraryService with filtering and caching logic in app/Services/GameLibraryService.php
- [ ] T032 [US2] Add Library namespace routes to routes/api.php (GET /v1/library, GET /v1/library/{key}, GET /v1/library/{key}/rules, GET /v1/library/{key}/entities)
- [ ] T033 [US2] Create GameLibraryData DTO in app/DataTransferObjects/Library/GameLibraryData.php
- [ ] T034 [US2] Create GameRulesData DTO in app/DataTransferObjects/Library/GameRulesData.php
- [ ] T035 [US2] Create GameEntityData DTO in app/DataTransferObjects/Library/GameEntityData.php
- [ ] T036 [US2] Delete old TitleController from app/Http/Controllers/Api/V1/TitleController.php

**Checkpoint**: Library endpoints operational - game discovery, rules, and asset retrieval functional

---

## Phase 5: User Story 3 - User Authentication & Account Management (Priority: P1)

**Goal**: Users can create accounts, authenticate through multiple methods, manage their profiles, track progression, view performance records, and receive personalized notifications.

**Independent Test**: Create test accounts, authenticate with different methods, update profiles, and verify data persistence without requiring active games.

### Implementation for User Story 3

#### Auth Namespace Controllers

- [ ] T037 [P] [US3] Create RegisterController in app/Http/Controllers/Api/V1/Auth/RegisterController.php
- [ ] T038 [P] [US3] Create LoginController in app/Http/Controllers/Api/V1/Auth/LoginController.php
- [ ] T039 [P] [US3] Create SocialAuthController in app/Http/Controllers/Api/V1/Auth/SocialAuthController.php
- [ ] T040 [P] [US3] Create LogoutController in app/Http/Controllers/Api/V1/Auth/LogoutController.php

#### Account Namespace Controllers

- [ ] T041 [P] [US3] Move ProfileController to namespace in app/Http/Controllers/Api/V1/Account/ProfileController.php
- [ ] T042 [P] [US3] Create ProgressionController (renamed from UserLevelsController) in app/Http/Controllers/Api/V1/Account/ProgressionController.php
- [ ] T043 [P] [US3] Create RecordsController (renamed from UserStatsController) in app/Http/Controllers/Api/V1/Account/RecordsController.php
- [ ] T044 [P] [US3] Create AlertsController (renamed from AlertController) in app/Http/Controllers/Api/V1/Account/AlertsController.php

#### Services and DTOs

- [ ] T045 [US3] Create AuthService with registration, login, social auth logic in app/Services/AuthService.php
- [ ] T046 [US3] Create ProfileService with update and validation logic in app/Services/ProfileService.php
- [ ] T047 [US3] Create ProgressionService with XP and level calculation in app/Services/ProgressionService.php
- [ ] T048 [US3] Add Auth namespace routes to routes/api.php (POST /v1/auth/register, POST /v1/auth/login, POST /v1/auth/social, POST /v1/auth/logout)
- [ ] T049 [US3] Add Account namespace routes to routes/api.php (GET /v1/account/profile, PATCH /v1/account/profile, GET /v1/account/progression, GET /v1/account/records, GET /v1/account/alerts, POST /v1/account/alerts/read)
- [ ] T050 [US3] Create ProfileData DTO in app/DataTransferObjects/Account/ProfileData.php
- [ ] T051 [US3] Create ProgressionData DTO in app/DataTransferObjects/Account/ProgressionData.php
- [ ] T052 [US3] Create RecordsData DTO in app/DataTransferObjects/Account/RecordsData.php
- [ ] T053 [US3] Create AlertData DTO in app/DataTransferObjects/Account/AlertData.php

#### Cleanup

- [ ] T054 [US3] Delete old UserLevelsController from app/Http/Controllers/Api/V1/UserLevelsController.php
- [ ] T055 [US3] Delete old UserStatsController from app/Http/Controllers/Api/V1/UserStatsController.php
- [ ] T056 [US3] Delete old AlertController from app/Http/Controllers/Api/V1/AlertController.php

**Checkpoint**: Auth and Account endpoints operational - authentication flows, profile management, progression tracking, and notifications functional

---

## Phase 6: User Story 4 - Floor Coordination & Matchmaking (Priority: P2)

**Goal**: Users coordinate with others to start games through public lobbies, quickplay signals, or direct invites. The "floor" serves as the assembly area before games begin.

**Independent Test**: Create lobbies, submit matchmaking signals, send proposals, and verify match creation without requiring complete game execution.

### Implementation for User Story 4

#### Models

- [ ] T057 [P] [US4] Create MatchmakingSignal model in app/Models/MatchmakingSignal.php
- [ ] T058 [P] [US4] Create Proposal model (replaces RematchRequest) in app/Models/Proposal.php

#### Controllers

- [ ] T059 [P] [US4] Refactor LobbyController to include player seat management in app/Http/Controllers/Api/V1/Floor/LobbyController.php
- [ ] T060 [P] [US4] Create SignalController (renamed from QuickplayController) in app/Http/Controllers/Api/V1/Floor/SignalController.php
- [ ] T061 [P] [US4] Create ProposalController (renamed from RematchController) in app/Http/Controllers/Api/V1/Floor/ProposalController.php

#### Services and Events

- [ ] T062 [US4] Create FloorCoordinationService with matchmaking logic in app/Services/FloorCoordinationService.php
- [ ] T063 [US4] Create LobbyPlayerJoined event with ShouldBroadcast in app/Events/LobbyPlayerJoined.php
- [ ] T064 [US4] Create ProposalSent event with ShouldBroadcast in app/Events/ProposalSent.php
- [ ] T065 [US4] Add Floor namespace routes to routes/api.php (GET /v1/floor/lobbies, POST /v1/floor/lobbies, POST /v1/floor/lobbies/{id}/seat, POST /v1/floor/signals, DELETE /v1/floor/signals/{id}, POST /v1/floor/proposals, POST /v1/floor/proposals/{id}/accept, POST /v1/floor/proposals/{id}/decline)
- [ ] T066 [US4] Create LobbyData DTO in app/DataTransferObjects/Floor/LobbyData.php
- [ ] T067 [US4] Create SignalData DTO in app/DataTransferObjects/Floor/SignalData.php
- [ ] T068 [US4] Create ProposalData DTO in app/DataTransferObjects/Floor/ProposalData.php

#### Cleanup

- [ ] T069 [US4] Delete old LobbyPlayerController from app/Http/Controllers/Api/V1/LobbyPlayerController.php
- [ ] T070 [US4] Delete old QuickplayController from app/Http/Controllers/Api/V1/QuickplayController.php
- [ ] T071 [US4] Delete old RematchController from app/Http/Controllers/Api/V1/RematchController.php

**Checkpoint**: Floor endpoints operational - lobby coordination, matchmaking signals, and proposals functional

---

## Phase 7: User Story 5 - Active Game Management (Priority: P2)

**Goal**: Users play games through live board state synchronization, action execution, turn management, and graceful exit options.

**Independent Test**: Create game instances, execute actions, verify state transitions, and test edge cases like conceding and abandoning.

### Implementation for User Story 5

#### Controllers

- [ ] T072 [P] [US5] Refactor GameController to simplify to listing and state retrieval in app/Http/Controllers/Api/V1/Games/GameController.php
- [ ] T073 [P] [US5] Move GameActionController to namespace in app/Http/Controllers/Api/V1/Games/GameActionController.php
- [ ] T074 [P] [US5] Create GameTurnController in app/Http/Controllers/Api/V1/Games/GameTurnController.php
- [ ] T075 [P] [US5] Create GameTimelineController in app/Http/Controllers/Api/V1/Games/GameTimelineController.php
- [ ] T076 [P] [US5] Create GameConcedeController in app/Http/Controllers/Api/V1/Games/GameConcedeController.php
- [ ] T077 [P] [US5] Create GameAbandonController in app/Http/Controllers/Api/V1/Games/GameAbandonController.php
- [ ] T078 [P] [US5] Create GameOutcomeController in app/Http/Controllers/Api/V1/Games/GameOutcomeController.php
- [ ] T079 [P] [US5] Create GameSyncController with catch-up endpoint in app/Http/Controllers/Api/V1/Games/GameSyncController.php

#### Events and Services

- [ ] T080 [US5] Create GameActionExecuted event with ShouldBroadcast in app/Events/GameActionExecuted.php
- [ ] T081 [US5] Create GameStatusChanged event with ShouldBroadcast in app/Events/GameStatusChanged.php
- [ ] T082 [US5] Create GameOutcomeService with XP and reward calculation in app/Services/GameOutcomeService.php
- [ ] T083 [US5] Add idempotency middleware to game action routes in routes/api.php
- [ ] T084 [US5] Add Games namespace routes to routes/api.php (GET /v1/games, GET /v1/games/{ulid}, POST /v1/games/{ulid}/actions, GET /v1/games/{ulid}/turn, GET /v1/games/{ulid}/timeline, POST /v1/games/{ulid}/concede, POST /v1/games/{ulid}/abandon, GET /v1/games/{ulid}/outcome, GET /v1/games/{ulid}/sync)
- [ ] T085 [US5] Create GameData DTO in app/DataTransferObjects/Games/GameData.php
- [ ] T086 [US5] Create GameActionData DTO in app/DataTransferObjects/Games/GameActionData.php
- [ ] T087 [US5] Create GameTurnData DTO in app/DataTransferObjects/Games/GameTurnData.php
- [ ] T088 [US5] Create GameOutcomeData DTO in app/DataTransferObjects/Games/GameOutcomeData.php

**Checkpoint**: Games endpoints operational - gameplay state sync, action execution, turn management, and exit options functional

---

## Phase 8: User Story 6 - Economy Management (Priority: P2)

**Goal**: Users view their virtual token/chip balances, track balance transactions, and maintain subscriptions through client applications. Approved clients can adjust user balances for entertainment purposes only.

**Independent Test**: Check balances, create balance adjustments, simulate chip allocation, and verify subscription plans without real payment processing.

### Implementation for User Story 6

#### Models

- [ ] T089 [P] [US6] Verify Balance model exists in app/Models/Balance.php (migration already created)
- [ ] T090 [P] [US6] Verify Transaction model exists in app/Models/Transaction.php (migration already created)

#### Controllers

- [ ] T091 [P] [US6] Create BalanceController in app/Http/Controllers/Api/V1/Economy/BalanceController.php
- [ ] T092 [P] [US6] Create TransactionController in app/Http/Controllers/Api/V1/Economy/TransactionController.php
- [ ] T093 [P] [US6] Create CashierController with approved client authorization in app/Http/Controllers/Api/V1/Economy/CashierController.php
- [ ] T094 [P] [US6] Create PlanController (extract from BillingController) in app/Http/Controllers/Api/V1/Economy/PlanController.php
- [ ] T095 [P] [US6] Create ReceiptController (extract from BillingController) in app/Http/Controllers/Api/V1/Economy/ReceiptController.php

#### Services

- [ ] T096 [US6] Create EconomyService with balance operations and transaction recording in app/Services/EconomyService.php
- [ ] T097 [US6] Create ReceiptVerificationService for Apple, Google, Telegram in app/Services/ReceiptVerificationService.php
- [ ] T098 [US6] Add idempotency middleware to cashier route in routes/api.php
- [ ] T099 [US6] Add Economy namespace routes to routes/api.php (GET /v1/economy/balance, GET /v1/economy/transactions, POST /v1/economy/cashier, GET /v1/economy/plans, POST /v1/economy/receipts/{provider})
- [ ] T100 [US6] Create BalanceData DTO in app/DataTransferObjects/Economy/BalanceData.php
- [ ] T101 [US6] Create TransactionData DTO in app/DataTransferObjects/Economy/TransactionData.php
- [ ] T102 [US6] Create PlanData DTO in app/DataTransferObjects/Economy/PlanData.php

#### Cleanup

- [ ] T103 [US6] Delete old BillingController from app/Http/Controllers/Api/V1/BillingController.php

**Checkpoint**: Economy endpoints operational - balance tracking, transactions, cashier operations, and subscription management functional

---

## Phase 9: User Story 7 - Real-Time Data Feeds (Priority: P3)

**Goal**: Dashboard applications, spectators, and players access high-frequency SSE streams of live game activity, win announcements, leaderboard changes, tournament updates, challenge activity, and achievement unlocks.

**Independent Test**: Connect to SSE endpoints and verify real-time updates are pushed correctly when relevant events occur.

### Implementation for User Story 7

#### Controllers

- [ ] T104 [P] [US7] Create LiveScoresController with SSE streams in app/Http/Controllers/Api/V1/Feeds/LiveScoresController.php
- [ ] T105 [P] [US7] Move LeaderboardController to namespace in app/Http/Controllers/Api/V1/Feeds/LeaderboardController.php
- [ ] T106 [P] [US7] Create CasinoFloorController with SSE streams in app/Http/Controllers/Api/V1/Feeds/CasinoFloorController.php

#### Services and Events

- [ ] T107 [US7] Create DataFeedService with SSE streaming logic in app/Services/DataFeedService.php
- [ ] T108 [US7] Create GameActivityEvent for feed broadcasting in app/Events/GameActivityEvent.php
- [ ] T109 [US7] Create LeaderboardUpdateEvent for feed broadcasting in app/Events/LeaderboardUpdateEvent.php
- [ ] T110 [US7] Add Feeds namespace routes to routes/api.php (GET /v1/feeds/games, GET /v1/feeds/wins, GET /v1/feeds/leaderboards, GET /v1/feeds/tournaments, GET /v1/feeds/challenges, GET /v1/feeds/achievements)
- [ ] T111 [US7] Create FeedEventData DTO in app/DataTransferObjects/Feeds/FeedEventData.php

**Checkpoint**: Feeds endpoints operational - real-time SSE streams for games, wins, leaderboards, tournaments, challenges, and achievements functional

---

## Phase 10: User Story 8 - Tournament & Competition Management (Priority: P3)

**Goal**: Users discover tournaments, register for events, track standings, and view bracket progression in structured competitions.

**Independent Test**: Create tournament structures, register players, advance brackets, and verify standings without requiring all games to be fully played.

### Implementation for User Story 8

#### Models

- [ ] T112 [P] [US8] Verify Tournament model exists in app/Models/Tournament.php (migration already created)
- [ ] T113 [P] [US8] Verify TournamentUser pivot model exists in app/Models/TournamentUser.php (migration already created)

#### Controllers

- [ ] T114 [P] [US8] Create CompetitionController in app/Http/Controllers/Api/V1/Competitions/CompetitionController.php
- [ ] T115 [P] [US8] Create EntryController in app/Http/Controllers/Api/V1/Competitions/EntryController.php
- [ ] T116 [P] [US8] Create StructureController in app/Http/Controllers/Api/V1/Competitions/StructureController.php
- [ ] T117 [P] [US8] Create BracketController in app/Http/Controllers/Api/V1/Competitions/BracketController.php
- [ ] T118 [P] [US8] Create StandingsController in app/Http/Controllers/Api/V1/Competitions/StandingsController.php

#### Services and Events

- [ ] T119 [US8] Create CompetitionService with bracket management and advancement logic in app/Services/CompetitionService.php
- [ ] T120 [US8] Create TournamentBracketUpdated event with ShouldBroadcast in app/Events/TournamentBracketUpdated.php
- [ ] T121 [US8] Add idempotency middleware to tournament entry route in routes/api.php
- [ ] T122 [US8] Add Competitions namespace routes to routes/api.php (GET /v1/competitions, GET /v1/competitions/{ulid}, POST /v1/competitions/{ulid}/enter, GET /v1/competitions/{ulid}/structure, GET /v1/competitions/{ulid}/bracket, GET /v1/competitions/{ulid}/standings)
- [ ] T123 [US8] Create CompetitionData DTO in app/DataTransferObjects/Competitions/CompetitionData.php
- [ ] T124 [US8] Create BracketData DTO in app/DataTransferObjects/Competitions/BracketData.php
- [ ] T125 [US8] Create StandingData DTO in app/DataTransferObjects/Competitions/StandingData.php

**Checkpoint**: Competition endpoints operational - tournament discovery, registration, structure, brackets, and standings functional

---

## Phase 11: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] T126 [P] Update quickstart.md with all new endpoint examples and authentication flows
- [ ] T127 [P] Update IMPLEMENTATION_NOTES.md with final route mappings
- [ ] T128 [P] Update CONTROLLER_MIGRATION_CHECKLIST.md with completion status
- [ ] T129 Code review and refactoring across all namespaces for consistency
- [ ] T130 Performance optimization: Add query caching for library endpoints
- [ ] T131 Performance optimization: Add eager loading for game state queries
- [ ] T132 Security audit: Verify all routes have proper authentication middleware
- [ ] T133 Security audit: Verify idempotency keys are enforced on all state-mutating operations
- [ ] T134 [P] Documentation: Generate OpenAPI spec from controllers using Laravel OpenAPI annotations
- [ ] T135 [P] Documentation: Add inline PHPDoc comments to all public controller methods
- [ ] T136 Run Pest test suite to validate existing tests still pass with new structure
- [ ] T137 Run Laravel Pint code formatter on all modified files
- [ ] T138 Run PHPStan static analysis on new controllers and services
- [ ] T139 Verify all old controller files have been deleted
- [ ] T140 Run quickstart.md validation scripts to ensure all examples work

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-10)**: All depend on Foundational phase completion
  - US1 (P1) can start after Phase 2 - No dependencies on other stories
  - US2 (P1) can start after Phase 2 - No dependencies on other stories
  - US3 (P1) can start after Phase 2 - No dependencies on other stories
  - US4 (P2) can start after Phase 2 - May integrate with US3 for user context
  - US5 (P2) can start after Phase 2 - May integrate with US4 for game creation
  - US6 (P2) can start after Phase 2 - May integrate with US3, US5, US8 for balance operations
  - US7 (P3) depends on US3, US4, US5, US8 being functional - Broadcasts events from other stories
  - US8 (P3) depends on US5, US6 being functional - Creates games and manages buy-ins
- **Polish (Phase 11)**: Depends on all desired user stories being complete

### User Story Priority Grouping

**P1 Stories (MVP Core)** - Can be worked on in parallel after Phase 2:
- US1: System Health & Configuration Access
- US2: Game Library Discovery
- US3: User Authentication & Account Management

**P2 Stories (Essential Features)** - Can start after Phase 2, may have light dependencies:
- US4: Floor Coordination & Matchmaking
- US5: Active Game Management
- US6: Economy Management

**P3 Stories (Enhancement Features)** - Should wait for P2 completion:
- US7: Real-Time Data Feeds (depends on US3, US4, US5, US8)
- US8: Tournament & Competition Management (depends on US5, US6)

### Within Each User Story

- Models before services
- Services before controllers
- Controllers before routes
- DTOs can be created in parallel with controllers
- Events can be created in parallel with services
- Cleanup tasks at the end of each story phase

### Parallel Opportunities

**Setup Phase (1)**:
- T002, T003, T004, T005 can run in parallel

**Foundational Phase (2)**:
- T008, T009, T010, T016, T017 can run in parallel after T006, T007 complete

**User Story 1 (Phase 3)**:
- T018, T019, T020, T021 can run in parallel (different controllers)
- T025, T026 can run in parallel (DTOs)
- T027, T028 can run in parallel (deletions)

**User Story 2 (Phase 4)**:
- T029, T030 can run in parallel
- T033, T034, T035 can run in parallel (DTOs)

**User Story 3 (Phase 5)**:
- T037, T038, T039, T040 can run in parallel (Auth controllers)
- T041, T042, T043, T044 can run in parallel (Account controllers)
- T050, T051, T052, T053 can run in parallel (DTOs)

**User Story 4 (Phase 6)**:
- T057, T058 can run in parallel (models)
- T059, T060, T061 can run in parallel (controllers)
- T066, T067, T068 can run in parallel (DTOs)

**User Story 5 (Phase 7)**:
- T072-T079 can run in parallel (8 controllers)
- T085, T086, T087, T088 can run in parallel (DTOs)

**User Story 6 (Phase 8)**:
- T089, T090 can run in parallel (model verification)
- T091-T095 can run in parallel (5 controllers)
- T100, T101, T102 can run in parallel (DTOs)

**User Story 7 (Phase 9)**:
- T104, T105, T106 can run in parallel (3 controllers)

**User Story 8 (Phase 10)**:
- T112, T113 can run in parallel (model verification)
- T114-T118 can run in parallel (5 controllers)
- T123, T124, T125 can run in parallel (DTOs)

**Polish Phase (11)**:
- T126, T127, T128 can run in parallel (documentation)
- T134, T135 can run in parallel (API documentation)

---

## Parallel Example: Maximum Concurrency

If you have 8+ developers, here's how to parallelize:

```bash
# Phase 1: Setup (4 developers)
Dev 1: T001
Dev 2: T002, T003
Dev 3: T004
Dev 4: T005

# Phase 2: Foundational (6 developers - CRITICAL PATH)
Dev 1: T006, T007 (sequential - blocking)
Dev 2: T008
Dev 3: T009
Dev 4: T010
Dev 5: T011, T012, T013 (migrations - sequential)
Dev 6: T014, T015 (migrations - sequential)
Dev 7: T016
Dev 8: T017

# Phase 3-5: P1 Stories (8 developers - MVP delivery)
Team A (Devs 1-2): US1 - System & Webhooks
Team B (Devs 3-4): US2 - Game Library
Team C (Devs 5-8): US3 - Auth & Account Management

# Phase 6-8: P2 Stories (8 developers)
Team A (Devs 1-3): US4 - Floor Coordination
Team B (Devs 4-5): US5 - Active Games
Team C (Devs 6-8): US6 - Economy

# Phase 9-10: P3 Stories (8 developers)
Team A (Devs 1-4): US7 - Real-Time Feeds
Team B (Devs 5-8): US8 - Tournaments

# Phase 11: Polish (all developers)
All: Code review, testing, documentation
```

---

## Implementation Strategy

### MVP Scope (Weeks 1-2)

Focus on P1 stories only for initial release:
- ✅ US1: System Health & Configuration Access
- ✅ US2: Game Library Discovery
- ✅ US3: User Authentication & Account Management

**Deliverable**: Users can register, authenticate, browse games, and platform health is monitored.

### Essential Features (Weeks 3-4)

Add P2 stories:
- ✅ US4: Floor Coordination & Matchmaking
- ✅ US5: Active Game Management
- ✅ US6: Economy Management

**Deliverable**: Complete gameplay loop with matchmaking, game execution, and economy tracking.

### Enhancement Features (Weeks 5-6)

Add P3 stories:
- ✅ US7: Real-Time Data Feeds
- ✅ US8: Tournament & Competition Management

**Deliverable**: Full feature platform with real-time updates and competitive tournaments.

### Polish & Launch (Week 7)

- Documentation completion
- Performance optimization
- Security hardening
- Final testing

**Deliverable**: Production-ready API with 99.9% uptime target.

---

## Task Summary

- **Total Tasks**: 140
- **Setup Phase**: 5 tasks
- **Foundational Phase**: 12 tasks (CRITICAL PATH)
- **User Story 1 (P1)**: 11 tasks
- **User Story 2 (P1)**: 8 tasks
- **User Story 3 (P1)**: 20 tasks
- **User Story 4 (P2)**: 15 tasks
- **User Story 5 (P2)**: 17 tasks
- **User Story 6 (P2)**: 15 tasks
- **User Story 7 (P3)**: 8 tasks
- **User Story 8 (P3)**: 14 tasks
- **Polish Phase**: 15 tasks

**Parallel Opportunities**: 80+ tasks marked [P] can run concurrently with proper team allocation

**Suggested MVP**: User Stories 1-3 (44 tasks, ~2 weeks with 4 developers)

**Format Validation**: ✅ All tasks follow checklist format with checkbox, ID, optional [P] marker, [Story] label for US phases, and file paths
