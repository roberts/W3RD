# Feature Specification: Production-Ready V1 API Structure

**Feature Branch**: `008-api-structure`  
**Created**: November 20, 2025  
**Status**: Draft  
**Input**: User description: "Finalize v1 API structure for production with headless infrastructure architecture, economy pivot, floor coordination namespace, and organized endpoint reference"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - System Health & Configuration Access (Priority: P1)

Platform operators and client applications need to verify service availability and retrieve global configuration before allowing users to interact with the system. This ensures clients can gracefully handle outages and maintain synchronized configuration.

**Why this priority**: Critical foundation for all other API interactions. Without health checks and config access, clients cannot reliably determine if the service is operational or what features are available.

**Independent Test**: Can be fully tested by making unauthenticated requests to system endpoints and verifying response codes and data structures without any game or user context.

**Acceptance Scenarios**:

1. **Given** the platform is operational, **When** a client requests `GET /v1/system/health`, **Then** the system returns a 200 status with service status indicators
2. **Given** the platform has global configuration, **When** a client requests `GET /v1/system/config`, **Then** the system returns current platform settings including supported games, features, and version information
3. **Given** multiple services exist, **When** a monitoring system checks health status, **Then** individual service health indicators are returned for database, cache, queue, and game engine
4. **Given** webhook events arrive from external providers, **When** the provider posts to `POST /v1/webhooks/{provider}`, **Then** the system processes the event and returns appropriate acknowledgment

---

### User Story 2 - Game Library Discovery (Priority: P1)

Users and client applications browse available games, view game metadata, access rule documentation, and cache static game assets before entering matchmaking or gameplay.

**Why this priority**: Users must discover what games they can play and understand game rules before participating. This is the entry point to the entire gaming experience.

**Independent Test**: Can be fully tested by querying library endpoints without authentication and verifying game metadata, rules, and asset definitions are returned correctly.

**Acceptance Scenarios**:

1. **Given** 1,000+ supported game titles exist, **When** a user browses `GET /v1/library`, **Then** games are returned with attributes including pacing (real-time, turn-based), complexity level, player count, and category tags
2. **Given** a user selects a specific game, **When** they request `GET /v1/library/{key}`, **Then** complete game metadata including title, description, thumbnail, player requirements, and average session duration is returned
3. **Given** a user wants to learn game rules, **When** they request `GET /v1/library/{key}/rules`, **Then** rule documentation in both human-readable and structured JSON format is returned for UI rendering
4. **Given** a client application needs static assets, **When** it requests `GET /v1/library/{key}/entities`, **Then** card definitions, unit stats, board configurations, and other cacheable game data is returned for local storage

---

### User Story 3 - User Authentication & Account Management (Priority: P1)

Users create accounts, authenticate through multiple methods, manage their profiles, track progression, view performance records, and receive personalized notifications.

**Why this priority**: User identity and account management are foundational requirements for personalized experiences, progression tracking, and secure access to platform features.

**Independent Test**: Can be fully tested by creating test accounts, authenticating with different methods, updating profiles, and verifying data persistence without requiring active games.

**Acceptance Scenarios**:

1. **Given** a new user wants to join, **When** they submit `POST /v1/auth/register` with email and password, **Then** a pending registration is created and verification email is sent
2. **Given** a user has multiple social accounts, **When** they use `POST /v1/auth/social` with a provider token (Google, Apple), **Then** their account is created or linked and an authentication token is returned
3. **Given** an authenticated user, **When** they request `GET /v1/account/profile`, **Then** their avatar, bio, username, and account settings are returned
4. **Given** a user wants to update their appearance, **When** they submit `PATCH /v1/account/profile` with new avatar or bio, **Then** their profile is updated and changes are reflected immediately
5. **Given** a user has earned experience, **When** they request `GET /v1/account/progression`, **Then** current XP, level, battle pass status, and progress to next milestone are returned
6. **Given** a competitive user, **When** they request `GET /v1/account/records`, **Then** win/loss statistics, ELO ratings per game, and performance metrics are returned
7. **Given** a user has pending notifications, **When** they request `GET /v1/account/alerts`, **Then** game invites, match results, friend requests, and system announcements are returned in reverse chronological order
8. **Given** a user reviews their alerts, **When** they submit `POST /v1/account/alerts/read` with alert IDs, **Then** specified alerts are marked as read and cleared from unread count

---

### User Story 4 - Floor Coordination & Matchmaking (Priority: P2)

Users coordinate with others to start games through public lobbies, quickplay signals, or direct invites. The "floor" serves as the assembly area before games begin.

**Why this priority**: Essential for connecting players and initiating gameplay, but depends on authentication and game library being functional first.

**Independent Test**: Can be fully tested by creating lobbies, submitting matchmaking signals, sending proposals, and verifying match creation without requiring complete game execution.

**Acceptance Scenarios**:

1. **Given** a user wants to host a casual match, **When** they browse `GET /v1/floor/lobbies`, **Then** public rooms with game type, host, player count, and join status are displayed
2. **Given** a user wants to create a private room, **When** they submit `POST /v1/floor/lobbies` with game settings, **Then** a lobby is created with a unique code and they become the host
3. **Given** an open lobby exists, **When** a user submits `POST /v1/floor/lobbies/{id}/seat`, **Then** they occupy an available player slot and other participants are notified
4. **Given** a user wants quick matchmaking, **When** they submit `POST /v1/floor/signals` with game preference and skill rating, **Then** their intent to play is registered and they enter the matchmaking pool
5. **Given** a user changes their mind, **When** they submit `DELETE /v1/floor/signals/{id}`, **Then** their matchmaking request is canceled and they are removed from the queue
6. **Given** a user wants to rematch or challenge someone, **When** they submit `POST /v1/floor/proposals` with target player and game type, **Then** a direct invite or rematch offer is created and delivered to the recipient
7. **Given** a user receives an invite, **When** they submit `POST /v1/floor/proposals/{id}/accept`, **Then** the offer is accepted and both players are redirected to a new game instance

---

### User Story 5 - Active Game Management (Priority: P2)

Users play games through live board state synchronization, action execution, turn management, and graceful exit options.

**Why this priority**: Core gameplay functionality, but requires floor coordination to create game instances first.

**Independent Test**: Can be fully tested by creating game instances, executing actions, verifying state transitions, and testing edge cases like conceding and abandoning.

**Acceptance Scenarios**:

1. **Given** a user has active games, **When** they request `GET /v1/games`, **Then** all their current and recent game sessions are listed with status, opponent, and last activity time
2. **Given** a user is in a game, **When** they request `GET /v1/games/{ulid}?since={timestamp}`, **Then** either complete board state or incremental patch/diff is returned based on sync efficiency
3. **Given** it is a user's turn, **When** they submit `POST /v1/games/{ulid}/actions` with action data and idempotency key, **Then** the move is validated, applied to game state, and result is returned immediately
4. **Given** a user is waiting for opponent, **When** they request `GET /v1/games/{ulid}/turn`, **Then** current turn timer information including time remaining and active player is returned
5. **Given** a user wants to review game progression, **When** they request `GET /v1/games/{ulid}/timeline`, **Then** ordered event stream with all actions, state changes, and timestamps is returned for replay
6. **Given** a user realizes they will lose, **When** they submit `POST /v1/games/{ulid}/concede`, **Then** they resign gracefully with a standard loss recorded
7. **Given** a user rage quits, **When** they submit `POST /v1/games/{ulid}/abandon`, **Then** they exit immediately with a penalty loss recorded
8. **Given** a game has completed, **When** either player requests `GET /v1/games/{ulid}/outcome`, **Then** detailed results including winner, final scores, XP earned, and rewards are returned

---

### User Story 6 - Economy Management (Priority: P2)

Users manage their financial resources, track transactions, buy into games with chips, cash out winnings, and maintain subscriptions.

**Why this priority**: Required for monetization but not essential for core gameplay in free modes.

**Independent Test**: Can be fully tested by checking balances, creating mock transactions, simulating chip management, and verifying subscription plans without real payment processing.

**Acceptance Scenarios**:

1. **Given** a user has an account, **When** they request `GET /v1/economy/balance`, **Then** their real money balance, bonus chips, and hard currency amounts are returned
2. **Given** a user has made purchases, **When** they request `GET /v1/economy/transactions`, **Then** deposit history, chip purchases, game buy-ins, and cash-outs are listed with timestamps and amounts
3. **Given** a user wants to join a cash game, **When** they submit `POST /v1/economy/cashier` with buy-in amount, **Then** chips are deducted from their balance and allocated to the game session
4. **Given** a user finishes a cash game, **When** they submit `POST /v1/economy/cashier` with cash-out amount, **Then** chips are converted back to balance and available for withdrawal
5. **Given** subscription tiers exist, **When** a user requests `GET /v1/economy/plans`, **Then** available tiers with pricing, benefits, and feature access are returned
6. **Given** a user purchases via mobile platform, **When** they submit `POST /v1/economy/receipts/{provider}` with receipt data, **Then** the purchase is verified with Apple, Google, or Telegram and credits are applied

---

### User Story 7 - Real-Time Data Feeds (Priority: P3)

Dashboard applications and spectators access high-frequency streams of live game scores and casino floor activity.

**Why this priority**: Enhancement feature for observers and analytics, not required for player participation.

**Independent Test**: Can be fully tested by connecting to SSE endpoints and verifying real-time updates are pushed correctly when game events occur.

**Acceptance Scenarios**:

1. **Given** games are in progress, **When** a dashboard connects to `GET /v1/feeds/live-scores`, **Then** an SSE stream delivers global score updates as games progress
2. **Given** drop-in tables exist, **When** a client connects to `GET /v1/feeds/casino-floor`, **Then** real-time status updates about table availability, player counts, and pot sizes are streamed

---

### User Story 8 - Tournament & Competition Management (Priority: P3)

Users discover tournaments, register for events, track standings, and view bracket progression in structured competitions.

**Why this priority**: Advanced meta-layer feature that builds on core gameplay and economy systems.

**Independent Test**: Can be fully tested by creating tournament structures, registering players, advancing brackets, and verifying standings without requiring all games to be fully played.

**Acceptance Scenarios**:

1. **Given** tournaments are scheduled, **When** a user requests `GET /v1/competitions`, **Then** active tournaments with buy-in costs, prize pools, start times, and player counts are listed
2. **Given** a user wants to compete, **When** they submit `POST /v1/competitions/{id}/enter`, **Then** they are registered, buy-in is deducted, and they receive bracket assignment
3. **Given** a tournament has structured phases, **When** an organizer requests `GET /v1/competitions/{id}/structure`, **Then** phase rules including blind levels, time limits, and advancement criteria are returned
4. **Given** a tournament uses elimination brackets, **When** anyone requests `GET /v1/competitions/{id}/bracket`, **Then** the tournament tree showing matches, winners, and upcoming rounds is returned
5. **Given** a tournament is in progress, **When** anyone requests `GET /v1/competitions/{id}/standings`, **Then** global event leaderboard with rankings, scores, and elimination status is returned

---

### Edge Cases

- What happens when a user attempts to join a game lobby that fills up during their request processing?
- How does the system handle duplicate action submissions with the same idempotency key?
- What happens if a user loses network connection during their turn in a real-time game?
- How does the system prevent race conditions when multiple users try to sit in the same lobby seat simultaneously?
- What happens when a webhook arrives from a provider for an unknown or deleted user?
- How does the system handle partial game state synchronization when network is unreliable?
- What happens if a user attempts to cash out chips while they are still in an active game?
- How does the system handle expired turn timers when players fail to act?
- What happens when a tournament bracket needs restructuring due to player disqualification?
- How does the system manage resource limits when thousands of concurrent SSE connections exist?
- What happens if a user receives conflicting proposals (multiple invites at the same time)?
- How does the system handle subscription verification failures from mobile platforms?

## Requirements *(mandatory)*

### Functional Requirements

#### System & Infrastructure (FR-S)

- **FR-S-001**: System MUST provide a health check endpoint that returns operational status of all critical services including database, cache, queue, and game engine
- **FR-S-002**: System MUST provide authoritative server time for synchronizing client actions and resolving timing disputes
- **FR-S-003**: System MUST expose global platform configuration including feature flags, supported games, API version, and maintenance windows
- **FR-S-004**: System MUST accept webhook events from external providers (Stripe, Apple, Google, Telegram) and process them asynchronously
- **FR-S-005**: System MUST validate webhook signatures to prevent unauthorized event injection
- **FR-S-006**: System MUST be versioned (v1) to support future API evolution without breaking existing clients

#### Game Library (FR-L)

- **FR-L-001**: System MUST provide paginated listing of all available game titles with metadata including name, description, player count, pacing type, and complexity level
- **FR-L-002**: System MUST support filtering game library by pacing (real-time, turn-based), player count (2-player, multi-player), and category tags
- **FR-L-003**: System MUST return detailed game metadata for individual titles including rules, average session duration, and thumbnail assets
- **FR-L-004**: System MUST provide game rule documentation in both human-readable text and structured JSON format for UI rendering
- **FR-L-005**: System MUST expose static game entity definitions (cards, units, boards) for client-side caching to reduce bandwidth
- **FR-L-006**: Game library endpoints MUST be publicly accessible without authentication to enable discovery before account creation

#### Authentication (FR-A)

- **FR-A-001**: System MUST support email/password registration with email verification workflow
- **FR-A-002**: System MUST support social authentication via Google and Apple OAuth2 providers
- **FR-A-003**: System MUST generate secure bearer tokens for authenticated sessions using industry-standard token management
- **FR-A-004**: System MUST require client key (`X-Client-Key` header) on all API requests to identify the calling application
- **FR-A-005**: System MUST support token revocation through logout endpoint
- **FR-A-006**: System MUST enforce password complexity requirements and protect against common attack vectors
- **FR-A-007**: System MUST support social account linking to allow users to connect multiple authentication methods to one account

#### Account Management (FR-M)

- **FR-M-001**: System MUST allow users to view and update their profile including username, avatar, and bio
- **FR-M-002**: System MUST track user progression including XP, levels, and battle pass status across all games
- **FR-M-003**: System MUST maintain performance records including win/loss statistics and ELO ratings per game title
- **FR-M-004**: System MUST deliver user notifications including game invites, match results, friend requests, and system announcements
- **FR-M-005**: System MUST support marking alerts as read individually or in bulk
- **FR-M-006**: System MUST enforce unique username constraints while allowing display name customization
- **FR-M-007**: System MUST persist user preferences including notification settings and privacy controls

#### Floor Coordination (FR-F)

- **FR-F-001**: System MUST allow users to create public or private game lobbies with configurable settings
- **FR-F-002**: System MUST allow users to browse and join available lobbies based on game type and visibility settings
- **FR-F-003**: System MUST manage lobby player slots and prevent exceeding maximum player capacity
- **FR-F-004**: System MUST support matchmaking signals (quickplay/ranked) that express player intent to play specific game types
- **FR-F-005**: System MUST match compatible players based on skill rating, game preference, and connection quality
- **FR-F-006**: System MUST allow users to cancel matchmaking requests before being matched
- **FR-F-007**: System MUST support direct challenge proposals (invites and rematch offers) between specific users
- **FR-F-008**: System MUST notify proposal recipients and allow acceptance or implicit decline through timeout
- **FR-F-009**: System MUST transition accepted proposals into active game instances and redirect participants
- **FR-F-010**: System MUST prevent users from entering multiple concurrent matchmaking queues or lobbies simultaneously

#### Active Game Management (FR-G)

- **FR-G-001**: System MUST provide current board state for all games a user is participating in
- **FR-G-002**: System MUST support efficient state synchronization using either full state or incremental patches based on `?since` timestamp parameter
- **FR-G-003**: System MUST validate all player actions against current game rules before applying state changes
- **FR-G-004**: System MUST enforce idempotency using `Idempotency-Key` header to prevent duplicate action processing
- **FR-G-005**: System MUST manage turn timers and automatically forfeit turns when time expires
- **FR-G-006**: System MUST provide explicit turn timer information including time remaining and active player
- **FR-G-007**: System MUST maintain complete action timeline for each game to support replay functionality
- **FR-G-008**: System MUST support graceful concession with standard loss penalty
- **FR-G-009**: System MUST support abandonment with increased penalty for rage quitting
- **FR-G-010**: System MUST calculate and return detailed game outcomes including winner, final scores, XP earned, and rewards
- **FR-G-011**: System MUST handle real-time game synchronization for simultaneous-action games
- **FR-G-012**: System MUST persist game state after each action to prevent data loss

#### Economy (FR-E)

- **FR-E-001**: System MUST maintain user balances for real money, bonus chips, and hard currency separately
- **FR-E-002**: System MUST record all financial transactions including deposits, purchases, game buy-ins, and cash-outs
- **FR-E-003**: System MUST provide transaction history with timestamps, amounts, and transaction types
- **FR-E-004**: System MUST support chip management through a cashier interface for buying into games and cashing out winnings
- **FR-E-005**: System MUST prevent users from cashing out chips that are currently in active game sessions
- **FR-E-006**: System MUST expose subscription plans with pricing, benefits, and feature access details
- **FR-E-007**: System MUST verify mobile platform receipts (Apple, Google, Telegram) before applying credits
- **FR-E-008**: System MUST enforce minimum and maximum transaction limits to comply with financial regulations
- **FR-E-009**: System MUST distinguish between withdrawable real money and non-withdrawable bonus funds

#### Data Feeds (FR-D)

- **FR-D-001**: System MUST provide Server-Sent Events (SSE) stream of live game scores for dashboard applications
- **FR-D-002**: System MUST provide SSE stream of casino floor status including table availability and player counts
- **FR-D-003**: System MUST handle high-frequency data streaming efficiently with thousands of concurrent connections
- **FR-D-004**: System MUST implement connection timeout and reconnection logic for interrupted streams

#### Competitions (FR-C)

- **FR-C-001**: System MUST allow users to browse active tournaments with buy-in costs, prize pools, and start times
- **FR-C-002**: System MUST support tournament registration with buy-in deduction and bracket assignment
- **FR-C-003**: System MUST define tournament structure including phase rules, blind levels, and advancement criteria
- **FR-C-004**: System MUST generate and maintain tournament brackets showing matches, winners, and progression
- **FR-C-005**: System MUST calculate and display real-time tournament standings and leaderboards
- **FR-C-006**: System MUST handle tournament advancement logic including elimination and reentry scenarios
- **FR-C-007**: System MUST distribute tournament prizes according to final standings

### Key Entities

- **User**: Platform account with authentication credentials, profile details, progression data, performance records, and financial balances
- **Game Title**: Static definition of a playable game including rules, entity definitions, player requirements, pacing type, and complexity level
- **Lobby**: Temporary coordination space where players gather before a game starts, with host, visibility settings, and player slots
- **Matchmaking Signal**: User intent to play expressing game preference, skill level, and availability for automated matching
- **Proposal**: Direct challenge or rematch offer between specific users with game settings and acceptance/decline state
- **Game Instance**: Active game session with current board state, player list, action timeline, and outcome when complete
- **Action**: Individual player move within a game with timestamp, validation result, and state change impact
- **Alert**: User notification for game invites, match results, friend requests, or system announcements
- **Transaction**: Financial record of deposits, purchases, game buy-ins, cash-outs, or subscription charges
- **Balance**: User's current holdings in real money, bonus chips, and hard currency
- **Subscription Plan**: Recurring payment tier with pricing, billing cycle, and feature access benefits
- **Tournament**: Structured competition with entry requirements, bracket structure, phase rules, and prize distribution
- **Data Feed**: Real-time event stream delivering live game updates or floor status to subscribed clients

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: API consumers can determine platform health and configuration within 100ms
- **SC-002**: Users can browse and filter 1,000+ game titles with metadata in under 2 seconds
- **SC-003**: Users can authenticate through any supported method (email, Google, Apple) within 3 seconds
- **SC-004**: Users can create and join lobbies with real-time participant updates delivered within 500ms
- **SC-005**: Matchmaking pairs compatible players within 30 seconds for popular game types
- **SC-006**: Game state synchronization delivers updates within 200ms for real-time games
- **SC-007**: API enforces idempotency to prevent 100% of duplicate action processing
- **SC-008**: Users can concede or abandon games with immediate state transition within 1 second
- **SC-009**: Users can view transaction history and current balance within 1 second
- **SC-010**: Mobile platform receipt verification completes within 5 seconds
- **SC-011**: SSE data feeds support 10,000+ concurrent connections without degradation
- **SC-012**: Tournament standings update within 2 seconds of game completion
- **SC-013**: API maintains 99.9% uptime for all critical endpoints during business hours
- **SC-014**: API documentation covers 100% of implemented endpoints with request/response examples
- **SC-015**: Zero implementation details (frameworks, databases, languages) exposed in API responses
- **SC-016**: All endpoints follow consistent naming conventions aligned with headless infrastructure architecture
- **SC-017**: API supports graceful version migration with backward compatibility for at least one major version
- **SC-018**: Webhook processing acknowledges receipt within 3 seconds and processes events asynchronously
- **SC-019**: Users experience zero data loss when network interruptions occur during gameplay
- **SC-020**: API rate limiting prevents abuse while allowing legitimate high-frequency usage patterns
