# Tasks: API Test Suite

**Feature**: 005-api-test-suite  
**Input**: Design documents from `/specs/005-api-test-suite/`  
**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/ ✅

**Tests**: This feature IS the test suite - all tasks are test-related

**Organization**: Tasks are grouped by user story (P1, P2, P3 priorities) to enable independent implementation and testing of each story.

## Format: `- [ ] [ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

All paths relative to repository root: `tests/Feature/Api/V1/`, `tests/Pest.php`, `tests/Feature/Helpers/`, `tests/Feature/Traits/`

---

## Phase 1: Setup (Test Infrastructure)

**Purpose**: Initialize test infrastructure and shared helpers that all test files will use

- [x] T001 Create directory structure for API v1 tests: `tests/Feature/Api/V1/`
- [x] T002 Create directory for test helpers: `tests/Feature/Helpers/`
- [x] T003 Create directory for test traits: `tests/Feature/Traits/`
- [x] T004 [P] Add global helper functions to `tests/Pest.php` (createAuthenticatedUser, assertValidationError, assertApiError)
- [x] T005 [P] Add custom expectations to `tests/Pest.php` (toHaveUserStructure, toHaveGameStructure, toHaveSubscriptionStructure, toBeSuccessfulApiResponse)

---

## Phase 2: Foundational (Shared Test Utilities)

**Purpose**: Core test utilities that MUST be complete before ANY user story tests can be implemented effectively

**⚠️ CRITICAL**: Complete these first to enable DRY principles across all test files

- [ ] T006 [P] Create AuthenticationHelper class in `tests/Feature/Helpers/AuthenticationHelper.php` with actingAs, loginAs, createToken methods
- [ ] T007 [P] Create GameHelper class in `tests/Feature/Helpers/GameHelper.php` with createGame, submitAction, assertGameState methods
- [ ] T008 [P] Create BillingHelper class in `tests/Feature/Helpers/BillingHelper.php` with createSubscription, verifyReceipt methods
- [ ] T009 [P] Create AssertionHelper class in `tests/Feature/Helpers/AssertionHelper.php` with assertJsonStructure, assertValidationError methods
- [x] T010 [P] Create CreatesGames trait in `tests/Feature/Traits/CreatesGames.php` for reusable game creation logic
- [x] T011 [P] Create CreatesSubscriptions trait in `tests/Feature/Traits/CreatesSubscriptions.php` for reusable subscription setup
- [x] T012 [P] Create InteractsWithWebSocket trait in `tests/Feature/Traits/InteractsWithWebSocket.php` for real-time testing helpers

**Checkpoint**: Test utilities ready - user story test implementation can now begin in parallel

---

## Phase 3: User Story 1 - API Client Integration Testing (Priority: P1) 🎯 MVP

**Goal**: Comprehensive tests for authentication endpoints enabling client developers to verify auth integration works correctly

**Independent Test**: Run `php artisan test tests/Feature/Api/V1/AuthenticationTest.php` - all auth flows tested independently

### Authentication Tests (User Story 1)

- [x] T013 [US1] Create AuthenticationTest.php file in `tests/Feature/Api/V1/AuthenticationTest.php` with describe() structure
- [x] T014 [US1] Add Registration tests: valid input creates user (201), duplicate email rejected (422), invalid email rejected (422), weak password rejected (422)
- [x] T015 [US1] Add Email Verification tests: valid token verifies email (200), invalid token rejected (422), expired token rejected (422)
- [x] T016 [US1] Add Login tests: valid credentials return token (200), invalid password rejected (422), unverified email rejected (403)
- [x] T017 [US1] Add Login rate limiting test: enforce 5 attempt limit returns 429
- [x] T018 [US1] Add Social Login tests with datasets: create user from OAuth (google, facebook, github) returns 201, link existing account, invalid token rejected (422)
- [x] T019 [US1] Add Token Management tests: get current user (200), update user profile (200), logout revokes tokens (200), expired token rejected (401)

**Checkpoint**: Authentication test suite complete - client developers can verify all auth flows work correctly

---

## Phase 4: User Story 2 - Game Lifecycle Testing (Priority: P1) 🎯 Core Functionality

**Goal**: Comprehensive tests for game endpoints from quickplay through completion enabling client developers to verify core game loop

**Independent Test**: Run `php artisan test tests/Feature/Api/V1/GameLifecycleTest.php tests/Feature/Api/V1/QuickplayTest.php tests/Feature/Api/V1/RematchTest.php` - complete game flow tested independently

### Game Lifecycle Tests (User Story 2)

- [x] T020 [P] [US2] Create GameLifecycleTest.php in `tests/Feature/Api/V1/GameLifecycleTest.php` with describe() structure
- [x] T021 [P] [US2] Create QuickplayTest.php in `tests/Feature/Api/V1/QuickplayTest.php` with describe() structure
- [x] T022 [P] [US2] Create RematchTest.php in `tests/Feature/Api/V1/RematchTest.php` with describe() structure

**Game Retrieval & Actions (GameLifecycleTest.php)**:

- [x] T023 [US2] Add Game Retrieval tests: list user games with pagination (200), show single game (200), unauthorized access rejected (403)
- [x] T024 [US2] Add Valid Move tests: accept DROP_PIECE (200), update game state correctly, advance turn, detect win condition, broadcast event
- [x] T025 [US2] Add Invalid Move tests: reject when not player turn (403), reject invalid column (422), reject in completed game (422), reject by non-player (403)
- [x] T026 [US2] Add Valid Options tests: return available moves for current player (200), empty array when not player turn (200)

**Quickplay Matchmaking (QuickplayTest.php)**:

- [x] T027 [US2] Add Join Queue tests: add user to queue (200), match two users immediately (200), return match_id when game created, enforce quota limits (429), reject suspended subscriptions (403)
- [x] T028 [US2] Add Leave Queue tests: remove user from queue (200), not in queue returns 404
- [x] T029 [US2] Add Accept Match tests: confirm acceptance (200), start game when all accept (200), reject expired match (422)

**Rematch Requests (RematchTest.php)**:

- [x] T030 [US2] Add Accept Rematch tests: create new game (201), swap player positions, notify requester
- [x] T031 [US2] Add Decline Rematch tests: update status (200), notify requester
- [x] T032 [US2] Add Edge Cases tests: reject expired rematch (422), reject already responded (422)

**Checkpoint**: Game lifecycle test suite complete - client developers can verify complete game flow works end-to-end

---

## Phase 5: User Story 3 - Billing & Subscription Testing (Priority: P2)

**Goal**: Comprehensive tests for billing across all platforms enabling client developers to verify monetization features work reliably

**Independent Test**: Run `php artisan test tests/Feature/Api/V1/BillingTest.php tests/Feature/Api/V1/StripeWebhookTest.php` - all billing flows tested independently

### Billing Tests (User Story 3)

- [ ] T033 [P] [US3] Create BillingTest.php in `tests/Feature/Api/V1/BillingTest.php` with describe() structure
- [ ] T034 [P] [US3] Create StripeWebhookTest.php in `tests/Feature/Api/V1/StripeWebhookTest.php` with describe() structure

**Billing Operations (BillingTest.php)**:

- [ ] T035 [US3] Add Plans & Status tests: list available plans (200), show current subscription status (200), show quota and usage (200)
- [ ] T036 [US3] Add Stripe Subscription tests: create subscription with payment method (201), create customer if not exists, apply trial period, return checkout session URL
- [ ] T037 [US3] Add IAP Verification tests with datasets: verify receipt from platform (apple, google, telegram) returns 200, create subscription on first purchase, update on renewal, handle validation failure (422)
- [ ] T038 [US3] Add Subscription Management tests: return Stripe portal URL (200), require active Stripe subscription (403)
- [ ] T039 [US3] Add Quota Enforcement tests: user exceeds quota returns 429, max strikes returns suspended status

**Stripe Webhooks (StripeWebhookTest.php)**:

- [ ] T040 [US3] Add Signature Verification tests: process valid signature (200), reject invalid signature (400)
- [ ] T041 [US3] Add Event Handling tests: handle subscription.created, subscription.updated, subscription.deleted, invoice.payment_succeeded, invoice.payment_failed
- [ ] T042 [US3] Add Idempotency test: ignore duplicate webhook events

**Checkpoint**: Billing test suite complete - client developers can verify all payment processing works correctly

---

## Phase 6: User Story 4 - Profile & Stats Testing (Priority: P2)

**Goal**: Tests for profile updates and stats tracking enabling client developers to verify user data accuracy

**Independent Test**: Run `php artisan test tests/Feature/Api/V1/ProfileTest.php tests/Feature/Api/V1/UserStatsTest.php tests/Feature/Api/V1/UserLevelsTest.php` - profile/stats tested independently

### Profile & Stats Tests (User Story 4)

- [ ] T043 [P] [US4] Create ProfileTest.php in `tests/Feature/Api/V1/ProfileTest.php` with describe() structure
- [ ] T044 [P] [US4] Create UserStatsTest.php in `tests/Feature/Api/V1/UserStatsTest.php` with describe() structure
- [ ] T045 [P] [US4] Create UserLevelsTest.php in `tests/Feature/Api/V1/UserLevelsTest.php` with describe() structure

**Profile Management (ProfileTest.php)**:

- [ ] T046 [US4] Add Profile Retrieval tests: show current user profile (200), include avatar and stats
- [ ] T047 [US4] Add Profile Update tests: update name (200), update avatar URL (200), reject invalid avatar URL (422), sanitize input data

**User Stats (UserStatsTest.php)**:

- [ ] T048 [US4] Add Stats Display tests: show aggregated statistics (200), include wins/losses/draws, include per-title breakdown, calculate win rate correctly

**User Levels (UserLevelsTest.php)**:

- [ ] T049 [US4] Add Levels Display tests: show levels for all titles (200), include current XP and next level threshold, show progression percentage
- [ ] T050 [US4] Add Level Progression tests: user earns XP triggers level-up event, total_wins and total_xp increment correctly after game completion

**Checkpoint**: Profile & stats test suite complete - client developers can verify progression systems work accurately

---

## Phase 7: User Story 5 - Lobby & Matchmaking Testing (Priority: P3)

**Goal**: Tests for lobby creation and invitations enabling client developers to verify social play features

**Independent Test**: Run `php artisan test tests/Feature/Api/V1/LobbyTest.php tests/Feature/Api/V1/LobbyPlayerTest.php` - lobby functionality tested independently

### Lobby Tests (User Story 5)

- [x] T051 [P] [US5] Create LobbyTest.php in `tests/Feature/Api/V1/LobbyTest.php` with describe() structure
- [x] T052 [P] [US5] Create LobbyPlayerTest.php in `tests/Feature/Api/V1/LobbyPlayerTest.php` with describe() structure

**Lobby Management (LobbyTest.php)**:

- [x] T053 [US5] Add Lobby Creation tests: create with valid mode (201), generate unique join code, set creator as host
- [x] T054 [US5] Add Lobby Listing tests: show user active lobbies (200), filter by status
- [x] T055 [US5] Add Ready Check tests: mark all players ready (200), create game when all ready (201), only host can start (403)
- [x] T056 [US5] Add Lobby Deletion tests: cancel by host (200), reject deletion by non-host (403)

**Lobby Players (LobbyPlayerTest.php)**:

- [x] T057 [US5] Add Player Invitation tests: invite user to lobby (201), send alert notification, reject duplicate invitation (422), reject when lobby full (422)
- [x] T058 [US5] Add Invitation Response tests: accept invitation (200), decline invitation (200), reject expired invitation (422)
- [x] T059 [US5] Add Player Removal tests: host kicks player (200), player leaves voluntarily (200), reject kick by non-host (403)

**Checkpoint**: Lobby test suite complete - client developers can verify social matchmaking works correctly

---

## Phase 8: User Story 6 - Alert & Notification Testing (Priority: P3)

**Goal**: Tests for alert system enabling client developers to verify notification features work correctly

**Independent Test**: Run `php artisan test tests/Feature/Api/V1/AlertTest.php` - alert functionality tested independently

### Alert Tests (User Story 6)

- [x] T060 [US6] Create AlertTest.php in `tests/Feature/Api/V1/AlertTest.php` with describe() structure
- [x] T061 [US6] Add Alert Listing tests: show unread alerts (200), paginate alerts, filter by type
- [x] T062 [US6] Add Mark as Read tests: mark single alert (200), mark multiple alerts (200), reject invalid alert ID (404)
- [x] T063 [US6] Add Real-time tests: game invite creates alert, alert with type 'game_invite' and is_read false

**Checkpoint**: Alert test suite complete - client developers can verify notification system works correctly

---

## Phase 9: Public Endpoints & Edge Cases (Cross-Cutting)

**Goal**: Tests for public endpoints and comprehensive edge case coverage across all features

**Independent Test**: Run `php artisan test tests/Feature/Api/V1/PublicEndpointsTest.php` - public endpoints tested independently

### Public Endpoints & Edge Cases

- [ ] T064 [P] Create PublicEndpointsTest.php in `tests/Feature/Api/V1/PublicEndpointsTest.php` with describe() structure
- [ ] T065 Add System Status tests: return API health status (200), no authentication required
- [ ] T066 Add Game Titles tests: list all available titles (200), include mode information
- [ ] T067 Add Game Rules tests: show rules for specific title (200), return 404 for invalid title
- [ ] T068 Add Leaderboards tests: show top players for title (200), paginate results, filter by time period

**Edge Cases (add to relevant test files)**:

- [ ] T069 [P] Add invalid game_id edge case to GameLifecycleTest.php: game not found returns 404
- [ ] T070 [P] Add concurrent actions edge case to GameLifecycleTest.php: handle simultaneous actions
- [ ] T071 [P] Add webhook ordering edge case to StripeWebhookTest.php: handle out-of-order webhooks
- [ ] T072 [P] Add expired OAuth edge case to AuthenticationTest.php: handle expired/revoked OAuth tokens
- [ ] T073 [P] Add malformed JSON edge case to all test files: reject malformed request bodies (400)
- [ ] T074 [P] Add database failure edge case to critical operations: handle connection failures gracefully
- [ ] T075 [P] Add expired invitation edge case to LobbyPlayerTest.php: reject expired lobby invitation (422)
- [ ] T076 [P] Add timezone edge case to timestamp comparisons: handle timezone differences correctly

**Checkpoint**: Public endpoints and edge cases complete - comprehensive coverage achieved

---

## Phase 10: Polish & Verification

**Goal**: Ensure test suite meets all success criteria and performance targets

- [ ] T077 Run full test suite and verify <30 second execution time: `php artisan test tests/Feature/Api/`
- [ ] T078 Verify 0% flakiness: run test suite 10 times consecutively, all must pass
- [ ] T079 Check test code for DRY violations: max 3 lines duplicated setup across files
- [ ] T080 Verify all 40+ endpoints have test coverage: cross-reference with `routes/api.php`
- [ ] T081 Review test failure messages: ensure actionable error messages with expected vs actual
- [ ] T082 [P] Add CI/CD pipeline configuration in `.github/workflows/tests.yml` for automated test execution
- [ ] T083 [P] Document test execution in repository README.md: add "Running Tests" section
- [ ] T084 Create test suite summary report: document coverage, execution time, edge cases covered

**Final Checkpoint**: Test suite ready for production use - meets all success criteria (SC-001 through SC-012)

---

## Task Summary

**Total Tasks**: 84
**Completed Tasks**: 45 (53%)
**Parallelizable Tasks**: 42 (50%)

### Tasks by User Story

- **Setup & Foundation**: 12 tasks (T001-T012) ✅ 8/12 completed
- **User Story 1 (P1 - Auth)**: 7 tasks (T013-T019) ✅ 7/7 completed
- **User Story 2 (P1 - Games)**: 13 tasks (T020-T032) ✅ 13/13 completed
- **User Story 3 (P2 - Billing)**: 10 tasks (T033-T042) ⏳ 0/10 completed
- **User Story 4 (P2 - Profile/Stats)**: 8 tasks (T043-T050) ⏳ 0/8 completed
- **User Story 5 (P3 - Lobbies)**: 9 tasks (T051-T059) ✅ 9/9 completed
- **User Story 6 (P3 - Alerts)**: 4 tasks (T060-T063) ✅ 4/4 completed
- **Public & Edge Cases**: 13 tasks (T064-T076) ⏳ 0/13 completed
- **Polish & Verification**: 8 tasks (T077-T084) ⏳ 0/8 completed

---

## Dependencies & Execution Strategy

### Critical Path

```text
Phase 1: Setup (T001-T005)
  ↓
Phase 2: Foundation (T006-T012) ← BLOCKING
  ↓
Phase 3-8: User Stories (T013-T063) ← Can run in parallel after foundation
  ↓
Phase 9: Public & Edge Cases (T064-T076) ← Can run in parallel with user stories
  ↓
Phase 10: Polish (T077-T084)
```

### Parallel Execution Opportunities

**After Foundation (T012) Complete**:

**Batch 1 - P1 Stories (run together)**:
- T013-T019: Authentication tests
- T020-T032: Game lifecycle tests

**Batch 2 - P2 Stories (run together)**:
- T033-T042: Billing tests
- T043-T050: Profile/Stats tests

**Batch 3 - P3 Stories + Public (run together)**:
- T051-T059: Lobby tests
- T060-T063: Alert tests
- T064-T076: Public endpoints & edge cases

**Batch 4 - Polish (sequential)**:
- T077-T084: Verification and documentation

### MVP Scope (Immediate Value)

**Recommended MVP**: User Story 1 only (Authentication Testing)
- Tasks: T001-T019 (19 tasks)
- Delivery time: ~2-3 hours
- Value: Client developers can immediately verify auth integration works correctly
- Independently testable: Run `php artisan test tests/Feature/Api/V1/AuthenticationTest.php`

**Extended MVP**: User Stories 1 + 2 (Auth + Games)
- Tasks: T001-T032 (32 tasks)
- Delivery time: ~4-6 hours
- Value: Complete testing of core user experience (auth + gameplay)
- Independently testable: Both test files run independently

---

## Implementation Strategy

### Independent Story Testing

Each user story is fully testable independently:

1. **US1 (Auth)**: `php artisan test tests/Feature/Api/V1/AuthenticationTest.php`
2. **US2 (Games)**: `php artisan test tests/Feature/Api/V1/GameLifecycleTest.php tests/Feature/Api/V1/QuickplayTest.php tests/Feature/Api/V1/RematchTest.php`
3. **US3 (Billing)**: `php artisan test tests/Feature/Api/V1/BillingTest.php tests/Feature/Api/V1/StripeWebhookTest.php`
4. **US4 (Profile)**: `php artisan test tests/Feature/Api/V1/ProfileTest.php tests/Feature/Api/V1/UserStatsTest.php tests/Feature/Api/V1/UserLevelsTest.php`
5. **US5 (Lobbies)**: `php artisan test tests/Feature/Api/V1/LobbyTest.php tests/Feature/Api/V1/LobbyPlayerTest.php`
6. **US6 (Alerts)**: `php artisan test tests/Feature/Api/V1/AlertTest.php`

### Incremental Delivery

- **Week 1**: Foundation + US1 (MVP) → Auth testing ready
- **Week 2**: US2 → Complete game flow testing
- **Week 3**: US3 + US4 → Monetization and progression testing
- **Week 4**: US5 + US6 + Public + Polish → Full coverage

### Quality Gates

After each user story:
1. Run story tests: All pass
2. Check execution time: Story tests < 5s
3. Verify DRY: No duplicated setup code
4. Review failures: Clear error messages

After complete suite:
1. Total execution: < 30s
2. Flakiness: 0% (10 consecutive runs pass)
3. Coverage: 100% of API endpoints
4. CI/CD: Green build

---

## Success Criteria Mapping

- **SC-001**: 100% endpoint coverage → Verified in T080
- **SC-002**: <30s execution → Verified in T077
- **SC-003**: 0% flakiness → Verified in T078
- **SC-004**: DRY principles → Verified in T079
- **SC-005**: Actionable error messages → Verified in T081
- **SC-006**: <10min to add tests → Enabled by T006-T012 helpers
- **SC-007**: Catches breaking changes → Comprehensive coverage T013-T076
- **SC-008**: 100% edge case coverage → T069-T076
- **SC-009**: Happy + error paths → All test tasks include both
- **SC-010**: Auth context helper → T006
- **SC-011**: JSON structure validators → T009
- **SC-012**: Database state assertions → Included in all tests

All success criteria addressable through task completion.
