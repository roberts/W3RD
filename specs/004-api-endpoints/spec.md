# Feature Specification: Core API Endpoints for Client Applications

**Feature Branch**: `004-api-endpoints`
**Created**: 2025-11-16
**Status**: Draft
**Input**: User description: "Create all the API endpoints we discussed earlier along with these billing endpoints and the refactoring of the few other existing API endpoints."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A Player Explores Games and Profiles (Priority: P1)

A user wants to browse the available games, check their own progress, and see how they stack up against others before deciding to play or subscribe.

**Why this priority**: This is the core discovery loop. Without it, users cannot understand what the platform offers, making it the most critical journey for new user engagement.

**Independent Test**: This can be fully tested by a client application that only has read-only access for an authenticated user. The user should be able to navigate between game lists, leaderboards, and their own profile seamlessly.

**Acceptance Scenarios**:

1.  **Given** a user is authenticated, **When** they open the app, **Then** the app can successfully call `GET /v1/titles` and display a list of playable games.
2.  **Given** the user has selected a game, **When** they navigate to its leaderboard, **Then** the app can call `GET /v1/leaderboards/{gameTitle}` and display the top players.
3.  **Given** the user navigates to their profile, **When** the page loads, **Then** the app successfully calls `GET /v1/me/stats` and `GET /v1/me/levels` to display their rank and game-specific progress.

---

### User Story 2 - A Player Manages and Resumes Active Games (Priority: P2)

A user who has already started one or more games wants to see their game history and jump back into an active session.

**Why this priority**: This is essential for player retention. Users expect to be able to leave and come back to a game in progress without losing their state.

**Independent Test**: Can be tested with a user who has at least one active game. The client should be able to fetch and display the list of games and successfully render the state of the selected active game.

**Acceptance Scenarios**:

1.  **Given** a user is authenticated and has active games, **When** they navigate to their "My Games" screen, **Then** the app calls `GET /v1/games` and displays a list of their active and past games.
2.  **Given** the user selects an active game from the list, **When** the game screen loads, **Then** the app calls `GET /v1/games/{gameUlid}` and correctly renders the current board state, player turn, and all other relevant game information.

---

### User Story 3 - A Web User Subscribes via Stripe (Priority: P3)

A user on a web client decides to purchase a subscription to unlock premium features.

**Why this priority**: This is the primary monetization path for web-based users and is critical for revenue generation.

**Independent Test**: Can be tested on a web client. A test user should be able to go from viewing plans to completing a mock checkout via Stripe.

**Acceptance Scenarios**:

1.  **Given** an un-subscribed user is on the billing page, **When** the page loads, **Then** the app calls `GET /v1/billing/plans` and displays the available subscription tiers.
2.  **Given** the user selects a plan and clicks "Subscribe", **When** the action is triggered, **Then** the app calls `POST /v1/billing/subscribe` and redirects the user to the returned Stripe Checkout URL.
3.  **Given** the user successfully completes the Stripe checkout, **When** they return to the app, **Then** a subsequent call to `GET /v1/billing/status` shows their new active subscription.

---

### User Story 4 - A Mobile User Subscribes via In-App Purchase (Priority: P4)

A user on a native mobile client (iOS or Android) purchases a subscription using the platform's native In-App Purchase flow.

**Why this priority**: This enables monetization on mobile platforms, which have strict rules about using native payment systems.

**Independent Test**: Requires a sandboxed mobile testing environment (e.g., TestFlight for iOS). A test user should be able to initiate an in-app purchase, "complete" it, and have their status updated on the backend.

**Acceptance Scenarios**:

1.  **Given** a user on an iOS device initiates an in-app purchase, **When** the purchase is successfully completed via the App Store, **Then** the client app sends the resulting receipt to `POST /v1/billing/apple/verify`.
2.  **Given** the API receives a valid receipt, **When** the verification is complete, **Then** the user's account is granted the subscription and this is reflected in `GET /v1/billing/status`.

### Edge Cases

-   **Invalid IDs**: How does the system respond when a request is made with a non-existent `gameUlid`, `title_slug`, or other invalid identifier? (Should return a 404 Not Found).
-   **Authorization**: What happens when an unauthenticated user tries to access an auth-required endpoint? (Should return a 401 Unauthorized). What happens if User A tries to access `GET /v1/games/{gameUlid}` for a game belonging to User B? (Should return a 403 Forbidden or 404 Not Found).
-   **Billing Verification**: How does the system handle an invalid or expired receipt/token sent to the `/verify` endpoints? (Should return a 422 Unprocessable Entity with a clear error message).
-   **Empty States**: How do list endpoints (`/games`, `/titles`) respond when there is no data to return? (Should return a 200 OK with an empty array).

## Requirements *(mandatory)*

### Functional Requirements

-   **FR-001**: The system **MUST** provide a `GET /v1/titles` endpoint to list all available game titles.
-   **FR-002**: The system **MUST** provide a `GET /v1/titles/{title_slug}` endpoint to return detailed rules and metadata for a specific game title.
-   **FR-003**: The system **MUST** provide a `GET /v1/games` endpoint to list all active and past games for the authenticated user.
-   **FR-004**: The system **MUST** provide a `GET /v1/games/{gameUlid}` endpoint to return the full, current state of a specific game instance.
-   **FR-005**: The system **MUST** provide a `GET /v1/me/stats` endpoint to return the authenticated user's global profile statistics.
-   **FR-006**: The system **MUST** provide a `GET /v1/me/levels` endpoint to return the authenticated user's game-specific levels and XP.
-   **FR-007**: The system **MUST** provide a `GET /v1/leaderboards/{gameTitle}` endpoint to return the public leaderboard for a specific game.
-   **FR-008**: The system **MUST** provide a `GET /v1/billing/plans` endpoint to list all purchasable subscription plans.
-   **FR-009**: The system **MUST** provide a `GET /v1/billing/status` endpoint to show the authenticated user's current subscription status.
-   **FR-010**: The system **MUST** provide a `POST /v1/billing/subscribe` endpoint that creates and returns a Stripe Checkout session URL.
-   **FR-011**: The system **MUST** provide a `POST /v1/billing/manage` endpoint that creates and returns a Stripe Customer Portal URL.
-   **FR-012**: The system **MUST** provide verification endpoints for third-party payments: `POST /v1/billing/apple/verify`, `POST /v1/billing/google/verify`, and `POST /v1/billing/telegram/verify`.
-   **FR-013**: The existing `GET /v1/games/{gameTitle}/rules` endpoint **MUST** be permanently redirected (301) or removed in favor of `GET /v1/titles/{title_slug}`.

### Key Entities *(include if feature involves data)*

-   **Title**: The definition and rules of a game that can be played (e.g., Validate-Four).
-   **Game**: A specific instance of a Title being played by a set of users, with its own state.
-   **User**: A registered player on the platform.
-   **Leaderboard**: A ranked list of Users based on their performance in a specific Title.
-   **Plan**: A definition of a subscription tier, including its price, name, and features.
-   **Subscription**: The record of a User's active Plan, including its start and end dates.

## Success Criteria *(mandatory)*

### Measurable Outcomes

-   **SC-001**: Average API response time for all new GET endpoints must be under 300ms for the 95th percentile.
-   **SC-002**: A third-party developer with API access can successfully build a client that integrates the full "Explore & View Profile" user story (US-1) within 3 working days.
-   **SC-003**: The user's subscription status, as returned by `GET /v1/billing/status`, must be updated and consistent across the platform within 30 seconds of a successful purchase confirmation from any provider (Stripe, Apple, Google, etc.).
-   **SC-004**: The API error rate for all new endpoints must remain below 0.1% under normal operating load.

