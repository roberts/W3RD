# Feature Specification: API Test Suite

**Feature Branch**: `005-api-test-suite`  
**Created**: 2025-01-16  
**Status**: Draft  
**Input**: User description: "tests for core api features necessary for clients using Pest v4 with grouped descriptions & dry principles"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - API Client Integration Testing (Priority: P1)

A client developer needs to verify their integration with the API works correctly before deploying to production. They need comprehensive tests that verify all critical endpoints (authentication, game flow, billing) function as expected with proper error handling.

**Why this priority**: This is the foundation - without reliable API integration tests, clients cannot confidently deploy or update their applications. This directly impacts time-to-market and reduces production incidents.

**Independent Test**: Can be fully tested by running the authentication test suite against a test environment and verifying successful login, token refresh, and protected endpoint access. Delivers immediate value by catching breaking changes in auth flow.

**Acceptance Scenarios**:

1. **Given** an unregistered email, **When** calling POST /api/register with valid data, **Then** returns 201 with user object and access token
2. **Given** valid credentials, **When** calling POST /api/login, **Then** returns 200 with access token and refresh token
3. **Given** an expired access token, **When** calling protected endpoint, **Then** returns 401 with clear error message
4. **Given** a valid refresh token, **When** calling POST /api/refresh, **Then** returns 200 with new access token
5. **Given** invalid social OAuth token, **When** calling POST /api/social-login, **Then** returns 422 with validation errors

---

### User Story 2 - Game Lifecycle Testing (Priority: P1)

A client developer needs to verify the complete game flow from matchmaking through game completion works correctly. This includes creating/joining games, submitting actions, handling invalid moves, and processing game outcomes.

**Why this priority**: Game functionality is the core product value. Testing the complete lifecycle ensures clients can deliver the primary user experience without bugs in critical paths.

**Independent Test**: Can be fully tested by creating a game via quickplay, submitting valid/invalid actions, and verifying completion states. Delivers immediate value by validating the core game loop works end-to-end.

**Acceptance Scenarios**:

1. **Given** an authenticated user, **When** calling POST /api/quickplay/join, **Then** returns 200 with match_id when match found
2. **Given** a matched game, **When** calling POST /api/games/{id}/actions with valid DROP_PIECE, **Then** returns 200 with updated game state
3. **Given** a game in progress, **When** submitting invalid move, **Then** returns 422 with specific error explaining why move is invalid
4. **Given** a winning move, **When** submitting action, **Then** returns 200 with game_status: 'completed' and winner_id
5. **Given** a completed game, **When** calling POST /api/games/{id}/rematch, **Then** returns 201 with new game_id

---

### User Story 3 - Billing & Subscription Testing (Priority: P2)

A client developer needs to verify subscription flows and IAP verification work correctly across multiple platforms (Stripe, Apple, Google, Telegram). This ensures monetization features function reliably.

**Why this priority**: Revenue-generating features must work flawlessly but can be tested independently from core gameplay. Critical for business but not blocking basic game functionality.

**Independent Test**: Can be fully tested by creating Stripe subscription, verifying webhook handling, and testing cancellation flow. Delivers value by ensuring payment processing is reliable.

**Acceptance Scenarios**:

1. **Given** valid Stripe payment method, **When** calling POST /api/billing/stripe/subscribe, **Then** returns 201 with subscription object and payment_intent
2. **Given** valid Apple receipt, **When** calling POST /api/billing/apple/verify, **Then** returns 200 with subscription details
3. **Given** an active subscription, **When** user exceeds quota, **Then** returns 429 with quota exceeded error
4. **Given** user reaches max strikes, **When** checking quota, **Then** returns subscription suspended status

---

### User Story 4 - Profile & Stats Testing (Priority: P2)

A client developer needs to verify user profile updates, stats tracking, and level progression work correctly. This ensures users see accurate data and achievements.

**Why this priority**: Important for user engagement and retention but not blocking core functionality. Can be developed/tested independently from game mechanics.

**Independent Test**: Can be fully tested by updating profile fields, playing games to earn XP, and verifying level-up events. Delivers value by ensuring progression systems are accurate.

**Acceptance Scenarios**:

1. **Given** authenticated user, **When** calling PATCH /api/profile with valid data, **Then** returns 200 with updated profile
2. **Given** user completes game, **When** checking stats, **Then** total_wins and total_xp are incremented correctly
3. **Given** user earns enough XP, **When** checking level, **Then** level_up event is triggered with new level

---

### User Story 5 - Lobby & Matchmaking Testing (Priority: P3)

A client developer needs to verify lobby creation, invitations, and private game setup work correctly. This enables social play features.

**Why this priority**: Important for social features but not critical for MVP. Users can play via quickplay without lobby functionality.

**Independent Test**: Can be fully tested by creating lobby, sending invitation, and starting game with accepted players. Delivers value by enabling private matches.

**Acceptance Scenarios**:

1. **Given** authenticated user, **When** calling POST /api/lobbies with valid mode, **Then** returns 201 with lobby object and join code
2. **Given** active lobby, **When** calling POST /api/lobbies/{id}/invite with friend's user_id, **Then** returns 201 and invitation sent
3. **Given** pending invitation, **When** recipient calls POST /api/lobbies/{id}/respond with accepted, **Then** returns 200 and user added to lobby
4. **Given** lobby with minimum players, **When** host starts game, **Then** returns 201 with new game_id

---

### User Story 6 - Alert & Notification Testing (Priority: P3)

A client developer needs to verify alert delivery, marking as read, and real-time updates work correctly. This enables user engagement features.

**Why this priority**: Enhances user experience but not critical for core functionality. Users can play games without alert system.

**Independent Test**: Can be fully tested by triggering game invite, verifying alert creation, and marking as read. Delivers value by ensuring notification system works.

**Acceptance Scenarios**:

1. **Given** user receives game invite, **When** checking GET /api/alerts, **Then** returns alert with type 'game_invite' and is_read: false
2. **Given** unread alerts, **When** calling POST /api/alerts/mark-read with alert IDs, **Then** returns 200 and alerts marked as read

---

### Edge Cases

- What happens when user sends game action with invalid game_id or game not found?
- How does system handle concurrent actions submitted simultaneously by multiple players?
- What happens when subscription webhook arrives out of order or duplicated?
- How does system handle expired/revoked OAuth tokens during social login?
- What happens when user tries to join quickplay with insufficient quota or suspended subscription?
- How does system handle malformed JSON in request bodies?
- What happens when rate limits are exceeded on any endpoint?
- How does system handle database connection failures during critical operations?
- What happens when user tries to accept already-expired lobby invitation?
- How does system handle timezone differences in timestamp comparisons?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Test suite MUST provide comprehensive coverage for all authentication endpoints (register, login, verify, social-login, refresh, logout)
- **FR-002**: Test suite MUST verify complete game lifecycle (quickplay join, action submission, move validation, game completion, rematch requests)
- **FR-003**: Test suite MUST validate billing operations across all platforms (Stripe, Apple, Google, Telegram) including subscription creation, verification, and quota enforcement
- **FR-004**: Test suite MUST organize tests into logical groups using Pest's `describe()` blocks (Authentication, Game Lifecycle, Billing, Matchmaking, Profile, Alerts)
- **FR-005**: Test suite MUST use Pest v4 syntax and features (datasets, higher-order tests, architectural testing)
- **FR-006**: Test suite MUST implement DRY principles through reusable test helpers, traits, and shared setup/teardown logic
- **FR-007**: Test suite MUST verify proper HTTP status codes (200, 201, 401, 403, 404, 422, 429) for all scenarios
- **FR-008**: Test suite MUST validate JSON response structures match documented API contracts
- **FR-009**: Test suite MUST test error handling for invalid inputs, missing fields, and malformed data
- **FR-010**: Test suite MUST verify authentication requirements (protected endpoints reject unauthenticated requests)
- **FR-011**: Test suite MUST test authorization rules (users can only access their own resources)
- **FR-012**: Test suite MUST validate pagination for list endpoints (games, alerts, leaderboards)
- **FR-013**: Test suite MUST verify real-time features using WebSocket connections (game updates, alert notifications)
- **FR-014**: Test suite MUST test rate limiting behavior and proper 429 responses
- **FR-015**: Test suite MUST verify database state changes after operations (games created, stats updated, subscriptions activated)
- **FR-016**: Test suite MUST use factories to generate test data consistently
- **FR-017**: Test suite MUST clean up test data between tests (database transactions or migrations)
- **FR-018**: Test suite MUST run in under 30 seconds for fast feedback loops
- **FR-019**: Test suite MUST provide clear failure messages indicating what assertion failed and why
- **FR-020**: Test suite MUST be executable in CI/CD pipeline without manual intervention

### Key Entities

- **API Endpoints**: All routes defined in `routes/api.php` requiring test coverage (40+ endpoints across auth, games, billing, profile, alerts, lobbies)
- **Test Groups**: Logical organization of tests by feature area (Authentication, Game Lifecycle, Billing, Matchmaking, Profile, Gamification, Alerts, Public)
- **Test Helpers**: Reusable functions for common operations (actingAs, createGame, createSubscription, assertGameState, assertJsonStructure)
- **Test Traits**: Shared behaviors like DatabaseTransactions, RefreshDatabase, WithFaker
- **Authentication Contexts**: Different user states (guest, authenticated, with-subscription, quota-exceeded, suspended)
- **Datasets**: Parameterized test inputs for game titles, billing platforms, invalid inputs, edge cases
- **Assertions**: Custom assertions for domain-specific validations (assertValidGameState, assertValidSubscription, assertQuotaEnforced)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: All API endpoints defined in routes/api.php have corresponding test coverage (100% endpoint coverage)
- **SC-002**: Test suite executes in under 30 seconds for fast feedback during development
- **SC-003**: All tests pass consistently in local development and CI/CD environments (0% flakiness rate)
- **SC-004**: Test code follows DRY principles with no more than 3 lines of duplicated setup code across test files
- **SC-005**: Every test failure provides actionable error message indicating exactly what failed and expected vs actual values
- **SC-006**: Developers can add new API endpoint tests in under 10 minutes using existing helpers and patterns
- **SC-007**: Test suite catches breaking API changes before deployment (proven by introducing intentional breaking change)
- **SC-008**: All edge cases and error scenarios documented in spec are covered by tests (100% edge case coverage)
- **SC-009**: Test suite verifies both happy path and error responses for every endpoint (minimum 2 tests per endpoint)
- **SC-010**: Authentication context switching uses shared helper reducing test setup from 10+ lines to 1 helper call
- **SC-011**: JSON response assertions use reusable structure validators reducing duplication from 15+ assertJsonPath calls to 1 assertJsonStructure call
- **SC-012**: Database state assertions verify expected changes after operations (e.g., user.total_wins incremented after game completion)
