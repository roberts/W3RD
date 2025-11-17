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

### Edge Cases

-   **Invalid IDs**: How does the system respond when a request is made with a non-existent `gameUlid`, `title_slug`, etc.? (Should return a 404 Not Found).
-   **Authorization**: What happens if User A tries to access a resource belonging to User B? (Should return a 403 Forbidden or 404 Not Found).
-   **Billing Verification**: How does the system handle an invalid, expired, or already-used receipt/token sent to the `/verify` endpoints? (Should return a 422 Unprocessable Entity or 409 Conflict).
-   **Webhook Failures**: What happens if a Stripe webhook is received but processing fails? (The system should log the error and be able to retry the webhook processing).
-   **Duplicate Webhooks**: How does the system handle receiving the same webhook event from Stripe multiple times? (It must be idempotent and not process the same event twice).

## Requirements *(mandatory)*

### Functional Requirements

#### Core API
-   **FR-001**: The system **MUST** provide a `GET /v1/titles` endpoint to list all available game titles.
-   **FR-002**: The system **MUST** provide a `GET /v1/titles/{title_slug}` endpoint to return detailed rules for a specific game title.
-   **FR-003**: The system **MUST** provide a `GET /v1/games` endpoint to list games for the authenticated user.
-   **FR-004**: The system **MUST** provide a `GET /v1/games/{gameUlid}` endpoint to return the state of a specific game.
-   **FR-005**: The system **MUST** provide a `GET /v1/me/stats` endpoint to return the user's global statistics.
-   **FR-006**: The system **MUST** provide a `GET /v1/me/levels` endpoint to return the user's game-specific levels.
-   **FR-007**: The system **MUST** provide a `GET /v1/leaderboards/{gameTitle}` endpoint for game leaderboards.
-   **FR-013**: The existing `GET /v1/games/{gameTitle}/rules` endpoint **MUST** be permanently redirected (301) or removed.

#### Billing & Subscriptions
-   **FR-008**: The system **MUST** provide a `GET /v1/billing/plans` endpoint to list all purchasable subscription plans.
-   **FR-009**: The system **MUST** provide a `GET /v1/billing/status` endpoint to show the user's current subscription status.
-   **FR-010**: The system **MUST** provide a `POST /v1/billing/subscribe` endpoint that creates a Stripe Checkout session.
-   **FR-011**: The system **MUST** provide a `POST /v1/billing/manage` endpoint that creates a Stripe Customer Portal session.
-   **FR-012**: The system **MUST** provide verification endpoints: `POST /v1/billing/apple/verify`, `POST /v1/billing/google/verify`, and `POST /v1/billing/telegram/verify`.
-   **FR-014**: The system **MUST** provide a secure webhook endpoint (`/webhooks/stripe`) to receive and process subscription events from Stripe (e.g., `checkout.session.completed`, `customer.subscription.updated`, `customer.subscription.deleted`).
-   **FR-015**: The system **MUST** provide a mechanism for administrators to grant or revoke lifetime memberships for any user.
-   **FR-016**: The `Subscription` model **MUST** support a "lifetime" status where the subscription does not expire.

### Key Entities *(include if feature involves data)*

-   **Title**: The definition and rules of a game that can be played.
-   **Game**: A specific instance of a Title being played by users.
-   **User**: A registered player on the platform.
-   **Leaderboard**: A ranked list of Users for a specific Title.
-   **Plan**: A definition of a subscription tier.
    -   Attributes: `name`, `price`, `currency`, `provider_plan_id` (e.g., Stripe Price ID), `description`.
-   **Subscription**: The record of a User's active Plan.
    -   Attributes: `user_id`, `plan_id`, `provider` (e.g., 'stripe', 'apple', 'google', 'telegram', 'admin'), `provider_subscription_id`, `status` (e.g., 'active', 'canceled', 'expired'), `expires_at` (nullable for lifetime).

## Success Criteria *(mandatory)*

### Measurable Outcomes

-   **SC-001**: Average API response time for all new GET endpoints must be under 300ms for the 95th percentile.
-   **SC-002**: A third-party developer can successfully integrate the full "Explore & View Profile" user story (US-1) within 3 working days.
-   **SC-003**: The user's subscription status, as returned by `GET /v1/billing/status`, must be updated and consistent across the platform within 30 seconds of a successful purchase confirmation from any provider (Stripe webhook, Apple/Google verification, etc.).
-   **SC-004**: The API error rate for all new endpoints must remain below 0.1% under normal operating load.
-   **SC-005**: The system must successfully process 99.9% of incoming Stripe webhooks without manual intervention.

