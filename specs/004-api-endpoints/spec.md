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
2.  **Given** the user has selected a game, **When** they navigate to its leaderboard, **Then** the app can call `GET /v1/leaderboard/{gameTitle}` and display the top players.
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

### User Story 3 - A Web User Subscribes and Manages via Stripe (Priority: P3)

A user on a web client decides to purchase a subscription and later manage it.

**Why this priority**: This is the primary monetization path for web-based users and is critical for revenue generation and self-service management.

**Independent Test**: Can be tested on a web client. A test user should be able to go from viewing plans to completing a mock checkout and accessing the customer portal.

**Acceptance Scenarios**:

1.  **Given** an un-subscribed user is on the billing page, **When** the page loads, **Then** the app calls `GET /v1/billing/plans` and displays the available subscription tiers.
2.  **Given** the user selects a plan and clicks "Subscribe", **When** the action is triggered, **Then** the app calls `POST /v1/billing/subscribe` and redirects the user to the returned Stripe Checkout URL.
3.  **Given** the user successfully completes the Stripe checkout, **When** they return to the app, **Then** a subsequent call to `GET /v1/billing/status` shows their new active subscription.
4.  **Given** a subscribed user wants to manage their billing, **When** they click "Manage Subscription", **Then** the app calls `POST /v1/billing/manage` and redirects them to the Stripe Customer Portal.

---

### User Story 4 - A Mobile User Subscribes via In-App Purchase (Priority: P4)

A user on a native mobile client (iOS or Android) purchases a subscription using the platform's native In-App Purchase flow.

**Why this priority**: This enables monetization on mobile platforms, which have strict rules about using native payment systems.

**Independent Test**: Requires a sandboxed mobile testing environment. A test user should be able to initiate an in-app purchase, "complete" it, and have their status updated on the backend.

**Acceptance Scenarios**:

1.  **Given** a user on an iOS device initiates an in-app purchase, **When** the purchase is successfully completed via the App Store, **Then** the client app sends the resulting receipt to `POST /v1/billing/apple/verify`.
2.  **Given** a user on an Android device initiates an in-app purchase, **When** the purchase is successfully completed via the Play Store, **Then** the client app sends the resulting purchase token to `POST /v1/billing/google/verify`.
3.  **Given** the API receives a valid receipt or token, **When** the verification is complete, **Then** the user's account is granted the subscription and this is reflected in `GET /v1/billing/status`.

---

### User Story 5 - A User Subscribes via Telegram (Priority: P5)

A user interacting with the platform via a Telegram Mini App purchases a subscription using Telegram Stars.

**Why this priority**: This captures a key market and revenue stream from the Telegram ecosystem.

**Independent Test**: Requires a test environment for a Telegram Mini App. A test user should be able to initiate and complete a payment flow within Telegram.

**Acceptance Scenarios**:

1.  **Given** a user in the Telegram Mini App initiates a payment, **When** the payment is successfully processed by Telegram, **Then** the Mini App sends the payment details to `POST /v1/billing/telegram/verify`.
2.  **Given** the API receives valid payment data from Telegram, **When** the verification is complete, **Then** the user's account is granted the subscription.

---

### User Story 6 - An Admin Grants Lifetime Membership (Priority: P6)

An administrator needs to grant a specific user a lifetime subscription for promotional, support, or partnership reasons.

**Why this priority**: This provides a necessary administrative tool for business operations without requiring a complex payment flow.

**Independent Test**: Can be tested via an admin panel or an artisan command. After the action is performed, the target user's billing status should reflect a non-expiring lifetime membership.

**Acceptance Scenarios**:

1.  **Given** an administrator has identified a user to receive lifetime access, **When** the admin executes the grant action (e.g., clicks a button in an admin panel or runs a command), **Then** the user's subscription record is created or updated to reflect a lifetime plan.
2.  **Given** a user has been granted lifetime membership, **When** they or the client calls `GET /v1/billing/status`, **Then** the status correctly indicates a non-expiring, permanent subscription.

---

### User Story 7 - A Developer or Admin Checks API Health (Priority: P7)

A client developer or a system administrator needs a simple way to confirm that the API is online and operational.

**Why this priority**: This is a non-user-facing but critical endpoint for monitoring, automated health checks, and client-side connection validation.

**Independent Test**: Can be tested with any HTTP client (like `curl`) without authentication. A successful test is receiving a `200 OK` response with the expected JSON body.

**Acceptance Scenarios**:

1.  **Given** the API is running, **When** a request is made to `GET /v1/status`, **Then** the system returns a `200 OK` response with a body of `{"status": "ok"}`.

---

### User Story 8 - A User Checks Their Notifications (Priority: P8)

A user wants to see a summary of events that occurred while they were offline, such as friend requests, game invites, or when it becomes their turn in a game.

**Why this priority**: This is crucial for re-engaging users and ensuring they don't miss important social or gameplay interactions, especially for asynchronous games.

**Independent Test**: Can be tested with a user who has received notifications. The client should be able to fetch, display, and mark notifications as read.

**Acceptance Scenarios**:

1.  **Given** a user has unread notifications, **When** they open their notifications screen, **Then** the app calls `GET /v1/me/notifications` and displays the list of notifications.
2.  **Given** the user has viewed their notifications, **When** they take an action to clear them, **Then** the app calls `POST /v1/me/notifications/mark-as-read` and the notifications are marked as read on the backend.

---

### User Story 9 - A Player Reviews a Game's Move History (Priority: P9)

A player in a complex or asynchronous game wants to review the entire sequence of moves to understand how the game reached its current state.

**Why this priority**: This is essential for strategy in turn-based games and provides a great replay/review feature for all games.

**Independent Test**: Can be tested on any game that has at least one move. The client should be able to fetch and display the list of moves in chronological order.

**Acceptance Scenarios**:

1.  **Given** a user is viewing a game, **When** they select the "History" option, **Then** the app calls `GET /v1/games/{gameUlid}/history` and displays a chronological list of all moves made in that game.

---

### User Story 10 - A User Customizes Their Public Profile (Priority: P5)

A user wants to personalize their public-facing profile by changing their avatar, writing a bio, and setting social links.

**Why this priority**: This is a key social and personalization feature that allows users to express their identity on the platform.

**Independent Test**: Can be tested by an authenticated user on their profile editing screen. The user should be able to fetch their current profile data, make changes, save them, and see the changes reflected.

**Acceptance Scenarios**:

1.  **Given** a user navigates to their "Edit Profile" screen, **When** the page loads, **Then** the app calls `GET /v1/me/profile` and populates the form with their current username, bio, and avatar.
2.  **Given** the user changes their bio and saves, **When** the action is triggered, **Then** the app calls `PATCH /v1/me/profile` with the new data, and a subsequent fetch confirms the update.

---

### User Story 11 - A Player Requests a Rematch (Priority: P3)

After completing a game, a player wants to quickly start a new game with the same opponent without going through the full matchmaking or lobby creation process.

**Why this priority**: This is a high-value feature for player retention and engagement. When players finish an enjoyable game, the immediate opportunity to play again captures momentum and keeps them engaged with the platform.

**Independent Test**: Can be tested with two users who have completed a game together. After the game ends, one player should be able to request a rematch, and the other should receive a notification and be able to accept or decline.

**Acceptance Scenarios**:

1.  **Given** a user has just completed a game, **When** they view the game results screen, **Then** the app displays a "Rematch" button.
2.  **Given** the user clicks "Rematch", **When** the action is triggered, **Then** the app calls `POST /v1/games/{gameUlid}/rematch` and receives a success response.
3.  **Given** a rematch has been requested, **When** the opponent opens their app, **Then** they receive a notification about the rematch request.
4.  **Given** the opponent accepts the rematch, **When** they respond, **Then** a new game is created with the same players and game title, and both players are redirected to the new game.
5.  **Given** the opponent declines or ignores the rematch for 5 minutes, **When** the timeout occurs, **Then** the rematch request is automatically expired and the requesting player is notified.

### Edge Cases

-   **Invalid IDs**: How does the system respond when a request is made with a non-existent `gameUlid`, `title_slug`, etc.? (Should return a 404 Not Found).
-   **Authorization**: What happens if User A tries to access a resource belonging to User B? (Should return a 403 Forbidden or 404 Not Found).
-   **Billing Verification**: How does the system handle an invalid or expired receipt/token sent to the `/verify` endpoints? (Should return a 422 Unprocessable Entity with a clear error message).
-   **Empty States**: How do list endpoints (`/games`, `/titles`) respond when there is no data to return? (Should return a 200 OK with an empty array).
-   **Notifications**: What happens when a user with no notifications calls the `GET /v1/me/notifications` endpoint? (Should return a 200 OK with an empty array).

## Requirements *(mandatory)*

### Functional Requirements

#### API Design Clarification: `/auth/user` vs. `/me/profile`

To ensure clarity for client developers, the distinction between these two resource paths is as follows:

*   **/v1/auth/user**: These endpoints manage a user's **private account and authentication credentials**. They are used for actions related to security and identity verification, such as updating an email address or changing a password. Data here is considered sensitive and is never exposed publicly.
*   **/v1/me/profile**: These endpoints manage a user's **public-facing social persona**. This includes customizable, non-sensitive data that other players can see, such as a username, bio, selected avatar, or social links.

#### Core API
-   **FR-001**: The system **MUST** provide a `GET /v1/titles` endpoint to list all available game titles.
-   **FR-002**: The system **MUST** provide a `GET /v1/titles/{title_slug}` endpoint to return detailed rules for a specific game title.
-   **FR-003**: The system **MUST** provide a `GET /v1/games` endpoint to list games for the authenticated user.
-   **FR-004**: The system **MUST** provide a `GET /v1/games/{gameUlid}` endpoint to return the state of a specific game.
-   **FR-005**: The system **MUST** provide a `GET /v1/me/stats` endpoint to return the user's global statistics.
-   **FR-006**: The system **MUST** provide a `GET /v1/me/levels` endpoint to return the user's game-specific levels.
-   **FR-007**: The system **MUST** provide a `GET /v1/leaderboard/{gameTitle}` endpoint for game leaderboards.
-   **FR-013**: The existing `GET /v1/games/{gameTitle}/rules` endpoint **MUST** be permanently redirected (301) or removed in favor of `GET /v1/titles/{gameTitle}/rules`.

#### User Profile & Persona
-   **FR-022**: The system **MUST** provide a `GET /v1/me/profile` endpoint to return the authenticated user's public-facing profile data (e.g., username, bio, avatar, join date, social links).
-   **FR-023**: The system **MUST** provide a `PATCH /v1/me/profile` endpoint for the authenticated user to update their public profile data.

#### Billing & Subscriptions
-   **FR-008**: The system **MUST** provide a `GET /v1/billing/plans` endpoint to list all purchasable subscription plans.
-   **FR-009**: The system **MUST** provide a `GET /v1/billing/status` endpoint to show the user's current subscription status.
-   **FR-010**: The system **MUST** provide a `POST /v1/billing/subscribe` endpoint that creates a Stripe Checkout session.
-   **FR-011**: The system **MUST** provide a `POST /v1/billing/manage` endpoint that creates a Stripe Customer Portal session.
-   **FR-012**: The system **MUST** provide verification endpoints: `POST /v1/billing/apple/verify`, `POST /v1/billing/google/verify`, and `POST /v1/billing/telegram/verify`.
-   **FR-014**: The system **MUST** provide a secure webhook endpoint (`/v1/stripe/webhook`) to receive and process subscription events from Stripe (e.g., `checkout.session.completed`, `customer.subscription.updated`, `customer.subscription.deleted`).
-   **FR-015**: The system **MUST** provide a mechanism for administrators to grant or revoke lifetime memberships for any user.
-   **FR-016**: The `Subscription` model **MUST** support a "lifetime" status where the subscription does not expire.

#### New Foundational & Gameplay Endpoints
-   **FR-017**: The system **MUST** provide an unauthenticated `GET /v1/status` endpoint that returns a JSON object indicating the API's operational status.
-   **FR-018**: The system **MUST** provide a `GET /v1/me/notifications` endpoint to list all notifications for the authenticated user.
-   **FR-019**: The system **MUST** provide an endpoint (e.g., `POST /v1/me/notifications/mark-as-read`) to mark notifications as read.
-   **FR-020**: The system **MUST** generate and persist notifications for key asynchronous events (e.g., friend request received, game invite, turn reminder).
-   **FR-021**: The system **MUST** provide a `GET /v1/games/{gameUlid}/history` endpoint that returns a chronological list of all moves/actions taken in that game.
-   **FR-024**: The system **MUST** provide a `POST /v1/games/{gameUlid}/rematch` endpoint that creates a rematch request.
-   **FR-025**: The system **MUST** provide a `POST /v1/games/rematch/{requestId}/accept` endpoint to accept a rematch request.
-   **FR-026**: The system **MUST** provide a `POST /v1/games/rematch/{requestId}/decline` endpoint to decline a rematch request.
-   **FR-027**: The system **MUST** automatically expire rematch requests after 5 minutes if not responded to.

### Key Entities *(include if feature involves data)*

-   **Title**: The definition and rules of a game that can be played.
-   **Game**: A specific instance of a Title being played by users.
-   **User**: A registered player on the platform. The user entity contains both private account data (email, password hash) and public profile data (username, bio, avatar_id).
-   **Leaderboard**: A ranked list of Users for a specific Title.
-   **Plan**: A definition of a subscription tier.
    -   Attributes: `name`, `price`, `currency`, `provider_plan_id` (e.g., Stripe Price ID), `description`.
-   **Subscription**: The record of a User's active Plan, including its start and end dates.
-   **Notification**: A record of an event that a user should be made aware of.
    -   Attributes: `id`, `user_id`, `type` (e.g., 'friend_request', 'game_invite'), `data` (JSON with context), `read_at` (nullable).
-   **RematchRequest**: A temporary record of a rematch challenge between players.
    -   Attributes: `id`, `original_game_id`, `requesting_user_id`, `opponent_user_id`, `status` (enum: `pending`, `accepted`, `declined`, `expired`), `expires_at`, `timestamps`.

## Success Criteria *(mandatory)*

### Measurable Outcomes

-   **SC-001**: Average API response time for all new GET endpoints must be under 300ms for the 95th percentile.
-   **SC-002**: A third-party developer can successfully integrate the full "Explore & View Profile" user story (US-1) within 3 working days.
-   **SC-003**: The user's subscription status, as returned by `GET /v1/billing/status`, must be updated and consistent across the platform within 30 seconds of a successful purchase confirmation from any provider (Stripe webhook, Apple/Google verification, etc.).
-   **SC-004**: The API error rate for all new endpoints must remain below 0.1% under normal operating load.
-   **SC-005**: The system must successfully process 99.9% of incoming Stripe webhooks without manual intervention.
-   **SC-006**: The `GET /v1/status` endpoint must have an average response time of under 50ms.
-   **SC-007**: A new notification must be available via the `GET /v1/me/notifications` endpoint within 5 seconds of the triggering event.

