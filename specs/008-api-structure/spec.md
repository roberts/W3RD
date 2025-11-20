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

Users view their virtual token/chip balances, track balance transactions, and maintain subscriptions through client applications. Approved clients can adjust user balances for entertainment purposes only.

**Important**: This system tracks virtual tokens and chips for entertainment only. No real money or cryptocurrency transactions occur. Balances are managed by approved client applications for their authenticated users. This is not a wagering or gambling system.

**Why this priority**: Required for platform monetization and game coordination but not essential for core gameplay mechanics.

**Independent Test**: Can be fully tested by checking balances, creating balance adjustments, simulating chip allocation, and verifying subscription plans without real payment processing.

**Acceptance Scenarios**:

1. **Given** a user has an account, **When** they request `GET /v1/economy/balance`, **Then** their virtual token balance and chip balance are returned
2. **Given** a user has balance history, **When** they request `GET /v1/economy/transactions`, **Then** balance adjustments (adds/removes) with timestamps, amounts, and references are listed
3. **Given** an approved client needs to adjust balance, **When** they submit `POST /v1/economy/cashier` with add/remove action and amount, **Then** user's token or chip balance is updated and transaction is recorded
4. **Given** subscription tiers exist, **When** a user requests `GET /v1/economy/plans`, **Then** available tiers with pricing, benefits, and feature access are returned
5. **Given** a user purchases via mobile platform, **When** they submit `POST /v1/economy/receipts/{provider}` with receipt data, **Then** the purchase is verified with Apple, Google, or Telegram and subscription is applied

---

### User Story 7 - Real-Time Data Feeds (Priority: P3)

Dashboard applications, spectators, and players access high-frequency SSE streams of live game activity, win announcements, leaderboard changes, tournament updates, challenge activity, and achievement unlocks.

**Why this priority**: Enhancement feature for engagement and social proof, not required for core gameplay.

**Independent Test**: Can be fully tested by connecting to SSE endpoints and verifying real-time updates are pushed correctly when relevant events occur.

**Acceptance Scenarios**:

1. **Given** games are in progress, **When** a spectator connects to `GET /v1/feeds/games`, **Then** an SSE stream delivers live public game starts, moves, and completions with player details
2. **Given** players are winning games, **When** a client connects to `GET /v1/feeds/wins`, **Then** real-time win announcements stream with winner username, game type, stakes, and outcome
3. **Given** leaderboard rankings change, **When** a dashboard connects to `GET /v1/feeds/leaderboards`, **Then** rank updates, new high scores, and daily/weekly leaders stream as they occur
4. **Given** tournaments are active, **When** a spectator connects to `GET /v1/feeds/tournaments`, **Then** tournament progress including round completions, bracket updates, and eliminations stream in real-time
5. **Given** players issue challenges, **When** a client connects to `GET /v1/feeds/challenges`, **Then** new challenges, acceptances, and completions stream with game type and stake details
6. **Given** achievements are unlocked, **When** a user connects to `GET /v1/feeds/achievements`, **Then** platform-wide achievement unlocks stream with player details and achievement rarity

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

**Note**: This system tracks virtual tokens and chips for entertainment purposes only. No real money or cryptocurrency is transacted. Balances are managed by approved client applications.

- **FR-E-001**: System MUST maintain user balances for virtual tokens and chips separately
- **FR-E-002**: System MUST record all balance transactions including adds, removes, and references
- **FR-E-003**: System MUST provide transaction history with timestamps, amounts, actions, and reference identifiers
- **FR-E-004**: System MUST support cashier interface allowing approved clients to add or remove tokens/chips from user balances
- **FR-E-005**: System MUST restrict cashier endpoint access to approved client applications only
- **FR-E-006**: System MUST validate all cashier operations include proper authentication and authorization
- **FR-E-007**: System MUST expose subscription plans with pricing, benefits, and feature access details
- **FR-E-008**: System MUST verify mobile platform receipts (Apple, Google, Telegram) before applying subscription benefits
- **FR-E-009**: System MUST track balance allocations to active game sessions for coordination purposes
- **FR-E-010**: System MUST include reference identifiers in all balance transactions for client-side tracking

#### Data Feeds (FR-D)

- **FR-D-001**: System MUST provide SSE stream of live public games showing game starts, moves, and completions with player details
- **FR-D-002**: System MUST provide SSE stream of win announcements with winner, game type, stakes, and outcomes
- **FR-D-003**: System MUST provide SSE stream of leaderboard updates including rank changes, new high scores, and period leaders
- **FR-D-004**: System MUST provide SSE stream of tournament progress including round completions, bracket updates, and eliminations
- **FR-D-005**: System MUST provide SSE stream of challenge activity showing new challenges, acceptances, and completions
- **FR-D-006**: System MUST provide SSE stream of platform-wide achievement unlocks with player details and rarity indicators
- **FR-D-007**: System MUST handle high-frequency data streaming efficiently with 10,000+ concurrent connections
- **FR-D-008**: System MUST implement connection timeout and reconnection logic for interrupted streams
- **FR-D-009**: System MUST filter feed data appropriately (e.g., only public games, only significant wins, only rare achievements)
- **FR-D-010**: System MUST include timestamp and event sequence identifiers in all feed messages for client-side ordering

#### Competitions (FR-C)

- **FR-C-001**: System MUST allow users to browse active tournaments with buy-in costs, prize pools, and start times
- **FR-C-002**: System MUST support tournament registration with buy-in deduction and bracket assignment
- **FR-C-003**: System MUST define tournament structure including phase rules, blind levels, and advancement criteria
- **FR-C-004**: System MUST generate and maintain tournament brackets showing matches, winners, and progression
- **FR-C-005**: System MUST calculate and display real-time tournament standings and leaderboards
- **FR-C-006**: System MUST handle tournament advancement logic including elimination and reentry scenarios
- **FR-C-007**: System MUST distribute tournament prizes according to final standings

#### Idempotency (FR-I)

- **FR-I-001**: System MUST accept idempotency keys for all state-mutating operations (actions, balance changes, tournament entry)
- **FR-I-002**: System MUST use idempotency key format: client-generated UUID v4 or ULID
- **FR-I-003**: System MUST cache idempotency keys for 24 hours after initial request
- **FR-I-004**: System MUST return identical response (including status code) for duplicate requests with same idempotency key
- **FR-I-005**: System MUST reject requests with malformed idempotency keys with 400 Bad Request
- **FR-I-006**: System MUST handle concurrent requests with same idempotency key (first wins, others wait for result)
- **FR-I-007**: System MUST require idempotency keys for: game actions, balance adjustments, tournament entry, proposal acceptance
- **FR-I-008**: System MUST store idempotency key with: request hash, response status, response body, timestamp
- **FR-I-009**: System MUST use Redis for idempotency key storage with automatic expiration
- **FR-I-010**: System MUST return `Idempotency-Key` header in response echoing the provided key

#### Error Handling (FR-E)

- **FR-EH-001**: System MUST return consistent JSON error schema for all failures
- **FR-EH-002**: System MUST include error code, message, and optional field-level errors in error responses
- **FR-EH-003**: System MUST use HTTP status codes semantically (400 validation, 401 auth, 403 forbidden, 404 not found, 409 conflict, 422 business rule, 429 rate limit, 500 server error, 503 unavailable)
- **FR-EH-004**: System MUST provide machine-readable error codes (e.g., LOBBY_FULL, INSUFFICIENT_BALANCE, TURN_NOT_YOURS, DUPLICATE_ACTION)
- **FR-EH-005**: System MUST include `Retry-After` header in 429 rate limit responses
- **FR-EH-006**: System MUST log all 5xx errors with request context for debugging
- **FR-EH-007**: System MUST return field-level validation errors in array format with field name, value, and constraint violated
- **FR-EH-008**: System MUST sanitize error messages to prevent information leakage (no stack traces, no database errors, no file paths in production)
- **FR-EH-009**: System MUST provide correlation ID in all error responses for support tracing
- **FR-EH-010**: System MUST distinguish between client errors (4xx) and server errors (5xx) consistently

#### Real-Time Sync (FR-RT)

- **FR-RT-001**: System MUST use Laravel Reverb for WebSocket server and Laravel Echo on clients via @gamerprotocol/ui npm package
- **FR-RT-002**: System MUST support private channels requiring authentication (user-specific game updates, alerts)
- **FR-RT-003**: System MUST support public channels for broadcast events (leaderboards, tournament updates, floor activity)
- **FR-RT-004**: System MUST authenticate WebSocket connections using Sanctum tokens
- **FR-RT-005**: System MUST send heartbeat/ping every 30 seconds to detect disconnections
- **FR-RT-006**: System MUST implement automatic reconnection with exponential backoff (1s, 2s, 4s, 8s, max 30s)
- **FR-RT-007**: System MUST include sequence IDs in all real-time events for ordering and gap detection
- **FR-RT-008**: System MUST provide catch-up endpoint for missed events when reconnecting (sync since last sequence ID)
- **FR-RT-009**: System MUST use Redis pub/sub for broadcasting events across multiple Reverb servers
- **FR-RT-010**: System MUST limit concurrent WebSocket connections per user (max 5 simultaneous devices)
- **FR-RT-011**: System MUST deliver game state updates as JSON patches/diffs for efficiency (full state only on initial load)
- **FR-RT-012**: System MUST namespace channels by resource type (game.{ulid}, lobby.{ulid}, user.{id}, tournament.{ulid})
- **FR-RT-013**: System MUST broadcast lobby events (player joined, ready state changed, game starting) within 200ms
- **FR-RT-014**: System MUST broadcast game events (action executed, turn changed, game completed) within 200ms
- **FR-RT-015**: System MUST use presence channels for lobby participant tracking with join/leave notifications
- **FR-RT-016**: System MUST cache channel subscriptions in Redis with 1-hour TTL for connection recovery
- **FR-RT-017**: System MUST throttle broadcast events to prevent message flooding (max 100 events/second per channel)
- **FR-RT-018**: System MUST gracefully degrade to HTTP polling if WebSocket connection fails after 3 retry attempts
- **FR-RT-019**: System MUST encrypt WebSocket traffic using WSS protocol
- **FR-RT-020**: System MUST provide connection status API endpoint for debugging (active connections, channels, memory usage)

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

## Technical Design *(mandatory)*

### Architecture Overview

**Namespace Organization**: The API is restructured into 9 logical namespaces that separate platform infrastructure from gameplay mechanics:

1. **System** (`/v1/system/*`) - Health monitoring, time sync, configuration
2. **Webhooks** (`/v1/webhooks/*`) - External provider event processing
3. **Library** (`/v1/library/*`) - Game discovery and rules
4. **Auth** (`/v1/auth/*`) - Authentication flows
5. **Account** (`/v1/account/*`) - User profile and progression
6. **Floor** (`/v1/floor/*`) - Matchmaking coordination (lobbies, signals, proposals)
7. **Games** (`/v1/games/*`) - Active gameplay sessions
8. **Economy** (`/v1/economy/*`) - Virtual balance and subscriptions
9. **Feeds** (`/v1/feeds/*`) - Real-time SSE data streams
10. **Competitions** (`/v1/competitions/*`) - Tournament management

**Design Principles**:
- **Headless API**: No frontend rendering, pure JSON responses
- **RESTful Resources**: Nouns for entities, HTTP verbs for actions
- **Namespace Isolation**: Related endpoints grouped for discoverability
- **Separate Controllers**: Each endpoint has dedicated controller (no monoliths)
- **No Backward Compatibility**: Clean break from legacy structure

---

### Phase 1: Controller Reorganization

**Objective**: Restructure all controllers from flat `Api/V1/` directory into namespace subdirectories.

**Migration Strategy**: Direct reorganization without backward compatibility. Delete old controllers after extracting logic.

#### Namespace Mapping

**1. System Namespace** (`Api/V1/System/`)

New Controllers:
- `HealthController` - Service health checks (database, cache, queue, game engine)
- `TimeController` - Authoritative server time
- `ConfigController` - Global platform configuration

Delete:
- ❌ `StatusController` → Logic moved to HealthController

---

**2. Webhooks Namespace** (`Api/V1/Webhooks/`)

New Controllers:
- `WebhookController` - Unified webhook handler with provider-specific methods
  - `stripe()` - Stripe payment events
  - `apple()` - Apple IAP notifications
  - `google()` - Google Play notifications  
  - `telegram()` - Telegram payment webhooks

Delete:
- ❌ `StripeWebhookController` → Logic moved to WebhookController

---

**3. Library Namespace** (`Api/V1/Library/`)

New Controllers:
- `GameLibraryController` - Game browsing, metadata, entity definitions
  - `index()` - Browse all games (paginated, filterable)
  - `show()` - Game details
  - `entities()` - Static game data (cards, units, boards)
- `GameRulesController` - Rules documentation (relocated, no changes)

Delete:
- ❌ `TitleController` → Logic moved to GameLibraryController

---

**4. Auth Namespace** (`Api/V1/Auth/`)

Relocated:
- `AuthController` - Single controller for all auth flows (cohesive domain)
  - `register()` - Account creation
  - `verify()` - Email verification
  - `login()` - Email/password authentication
  - `socialLogin()` - OAuth (Google, Apple)
  - `logout()` - Token revocation
  - `getUser()` - Current user data
  - `updateUser()` - Account updates

No deletions (existing controller moves to namespace folder)

---

**5. Account Namespace** (`Api/V1/Account/`)

New Controllers:
- `ProfileController` - User profile management (relocated)
  - `show()` - Get profile
  - `update()` - Update avatar/bio
- `ProgressionController` - XP, levels, battle pass
  - `show()` - Get progression data
- `RecordsController` - Win/loss stats, ELO ratings
  - `show()` - Get performance metrics
- `AlertsController` - Notifications
  - `index()` - List alerts
  - `markAsRead()` - Mark alerts read

Delete:
- ❌ `UserLevelsController` → Logic moved to ProgressionController
- ❌ `UserStatsController` → Logic moved to RecordsController
- ❌ `AlertController` → Renamed to AlertsController (plural)

---

**6. Floor Namespace** (`Api/V1/Floor/`)

New Controllers:
- `LobbyController` - Private room management + player seats (merged)
  - `index()` - Browse lobbies
  - `store()` - Create lobby
  - `show()` - Lobby details
  - `destroy()` - Close lobby
  - `readyCheck()` - Ready check
  - `joinSeat()` - Join lobby (merged from LobbyPlayerController)
  - `updateSeat()` - Change seat settings (merged)
  - `leaveSeat()` - Leave lobby (merged)
- `SignalController` - Matchmaking intent
  - `store()` - Submit matchmaking signal
  - `destroy()` - Cancel matchmaking
- `ProposalController` - Challenges and rematches (unified)
  - `store()` - Send challenge/rematch
  - `accept()` - Accept proposal
  - `decline()` - Decline proposal

Delete:
- ❌ `LobbyPlayerController` → Logic merged into LobbyController
- ❌ `QuickplayController` → Logic moved to SignalController
- ❌ `RematchController` → Logic moved to ProposalController

---

**7. Games Namespace** (`Api/V1/Games/`)

Refactored Controllers:
- `GameController` - Game listing and state retrieval (simplified)
  - `index()` - List user's games
  - `show()` - Get game state
  - Removed: `history()` → Moved to GameTimelineController
  - Removed: `forfeit()` → Moved to GameConcedeController
  - Removed: `requestRematch()` → Moved to Floor/ProposalController
- `GameActionController` - Action execution (kept)
  - `store()` - Execute action
  - `options()` - Available moves

New Controllers (extracted):
- `GameTurnController` - Turn timer management
  - `show()` - Time remaining, active player
- `GameTimelineController` - Event history
  - `index()` - Replay data, action log
- `GameConcedeController` - Graceful resignation
  - `store()` - Concede game
- `GameAbandonController` - Rage quit with penalty
  - `store()` - Abandon game
- `GameOutcomeController` - Final results
  - `show()` - Winner, scores, XP, rewards

No deletions (existing controllers refactored and expanded)

---

**8. Economy Namespace** (`Api/V1/Economy/`)

New Controllers:
- `BalanceController` - Virtual currency queries
  - `show()` - Get token/chip balance
- `TransactionController` - Balance history
  - `index()` - List transactions
- `CashierController` - Balance adjustments (approved clients only)
  - `store()` - Add/remove tokens or chips
- `PlanController` - Membership tiers
  - `index()` - List subscription plans
- `ReceiptController` - IAP verification
  - `verify()` - Apple/Google/Telegram receipt validation

Delete:
- ❌ `BillingController` → Logic split into PlanController + ReceiptController

---

**9. Feeds Namespace** (`Api/V1/Feeds/`)

New Controllers:
- `LeaderboardController` - Leaderboard SSE stream (relocated)
  - `stream()` - Real-time rank changes
- `LiveScoresController` - Game activity SSE streams
  - `games()` - Live game updates
  - `wins()` - Win announcements
  - `tournaments()` - Tournament progress
- `CasinoFloorController` - Floor activity SSE streams
  - `challenges()` - Challenge activity
  - `achievements()` - Achievement unlocks

Relocated:
- `LeaderboardController` - Moved from root V1 folder to Feeds namespace

---

**10. Competitions Namespace** (`Api/V1/Competitions/`)

New Controllers:
- `CompetitionController` - Tournament browsing
  - `index()` - List tournaments
  - `show()` - Tournament details
- `EntryController` - Registration
  - `store()` - Enter tournament
- `StructureController` - Tournament configuration
  - `show()` - Phase rules, blind levels
- `BracketController` - Tournament tree
  - `show()` - Bracket visualization
- `StandingsController` - Rankings
  - `index()` - Tournament leaderboard

---

### Phase 2: Route Restructuring

**Objective**: Rewrite all routes in `routes/api.php` to match new namespace organization.

**Changes**:
- Replace `/v1/status` with `/v1/system/health`
- Replace `/v1/stripe/webhook` with `/v1/webhooks/stripe`
- Replace `/v1/titles` with `/v1/library`
- Replace `/v1/billing/*` with `/v1/economy/*`
- Replace `/v1/me/*` with `/v1/account/*`
- Replace `/v1/games/quickplay/*` with `/v1/floor/signals/*`
- Replace `/v1/games/lobbies/*` with `/v1/floor/lobbies/*`
- Replace `/v1/games/rematch/*` with `/v1/floor/proposals/*`
- Add `/v1/feeds/*` for SSE streams
- Add `/v1/competitions/*` for tournaments

**No backward compatibility**: Old routes will be deleted entirely.

See `plan.md` Phase 2 section for complete route definitions.

---

### Phase 3: Idempotency Implementation

**Objective**: Implement idempotency protection for all state-mutating operations to prevent duplicate processing.

**Redis-Based Idempotency Storage**:

```php
// config/cache.php - Idempotency store configuration
'idempotency' => [
    'driver' => 'redis',
    'connection' => 'idempotency',
    'lock_connection' => 'default',
],

// config/database.php - Redis idempotency connection
'idempotency' => [
    'url' => env('REDIS_IDEMPOTENCY_URL'),
    'host' => env('REDIS_HOST', '127.0.0.1'),
    'port' => env('REDIS_PORT', '6379'),
    'database' => env('REDIS_IDEMPOTENCY_DB', '2'),
],
```

**Idempotency Middleware**:

```php
// app/Http/Middleware/EnsureIdempotency.php
class EnsureIdempotency
{
    public function handle(Request $request, Closure $next)
    {
        // Only for POST, PUT, PATCH, DELETE
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }
        
        $idempotencyKey = $request->header('Idempotency-Key');
        
        // Require idempotency key for critical operations
        if ($this->requiresIdempotency($request) && !$idempotencyKey) {
            return response()->json([
                'error' => 'IDEMPOTENCY_KEY_REQUIRED',
                'message' => 'Idempotency-Key header is required for this operation',
            ], 400);
        }
        
        if (!$idempotencyKey) {
            return $next($request);
        }
        
        // Validate key format (UUID v4 or ULID)
        if (!$this->isValidKey($idempotencyKey)) {
            return response()->json([
                'error' => 'INVALID_IDEMPOTENCY_KEY',
                'message' => 'Idempotency-Key must be a valid UUID v4 or ULID',
            ], 400);
        }
        
        $cacheKey = "idempotency:{$idempotencyKey}";
        
        // Check if request was already processed
        if (Cache::store('idempotency')->has($cacheKey)) {
            $cached = Cache::store('idempotency')->get($cacheKey);
            return response()->json($cached['body'], $cached['status'])
                ->header('Idempotency-Key', $idempotencyKey)
                ->header('X-Idempotent-Replay', 'true');
        }
        
        // Use lock to handle concurrent requests with same key
        $lock = Cache::lock("idempotency:lock:{$idempotencyKey}", 10);
        
        try {
            $lock->block(10); // Wait up to 10 seconds for lock
            
            // Double-check after acquiring lock
            if (Cache::store('idempotency')->has($cacheKey)) {
                $cached = Cache::store('idempotency')->get($cacheKey);
                return response()->json($cached['body'], $cached['status'])
                    ->header('Idempotency-Key', $idempotencyKey)
                    ->header('X-Idempotent-Replay', 'true');
            }
            
            // Process request
            $response = $next($request);
            
            // Cache successful responses (2xx) for 24 hours
            if ($response->status() >= 200 && $response->status() < 300) {
                Cache::store('idempotency')->put($cacheKey, [
                    'status' => $response->status(),
                    'body' => json_decode($response->content(), true),
                    'timestamp' => now()->toIso8601String(),
                ], 86400); // 24 hours
            }
            
            return $response->header('Idempotency-Key', $idempotencyKey);
            
        } catch (LockTimeoutException $e) {
            return response()->json([
                'error' => 'CONCURRENT_REQUEST',
                'message' => 'Another request with this idempotency key is being processed',
            ], 409);
        } finally {
            $lock->release();
        }
    }
    
    protected function requiresIdempotency(Request $request): bool
    {
        // Game actions, balance operations, tournament entry, proposals
        return $request->is('api/v1/games/*/actions') ||
               $request->is('api/v1/economy/cashier') ||
               $request->is('api/v1/competitions/*/enter') ||
               $request->is('api/v1/floor/proposals/*/accept');
    }
}
```

**Idempotency-Protected Endpoints**:
- `POST /v1/games/{ulid}/actions` - Game action execution
- `POST /v1/games/{ulid}/concede` - Concede game
- `POST /v1/games/{ulid}/abandon` - Abandon game
- `POST /v1/economy/cashier` - Balance adjustments
- `POST /v1/competitions/{ulid}/enter` - Tournament entry
- `POST /v1/floor/proposals/{ulid}/accept` - Accept proposal
- `POST /v1/floor/proposals/{ulid}/decline` - Decline proposal

---

### Phase 4: Error Response Standardization

**Objective**: Implement consistent error response format across all controllers.

**Standard Error Schema**:

```json
{
  "error": "ERROR_CODE",
  "message": "Human-readable error description",
  "correlation_id": "uuid-for-support-tracing",
  "errors": [
    {
      "field": "email",
      "value": "invalid-email",
      "constraint": "Must be a valid email address"
    }
  ]
}
```

**HTTP Status Code Usage**:

| Code | Usage | Example Error Codes |
|------|-------|-------------------|
| 400 | Bad Request - Malformed input | INVALID_JSON, MISSING_PARAMETER, INVALID_IDEMPOTENCY_KEY |
| 401 | Unauthorized - Missing/invalid token | UNAUTHENTICATED, TOKEN_EXPIRED, TOKEN_INVALID |
| 403 | Forbidden - Valid token, insufficient permissions | UNAUTHORIZED_CLIENT, CASHIER_NOT_APPROVED, NOT_GAME_PARTICIPANT |
| 404 | Not Found - Resource doesn't exist | GAME_NOT_FOUND, LOBBY_NOT_FOUND, USER_NOT_FOUND |
| 409 | Conflict - Resource state conflict | LOBBY_FULL, GAME_ALREADY_STARTED, DUPLICATE_ACTION, CONCURRENT_REQUEST |
| 422 | Unprocessable Entity - Business rule violation | INSUFFICIENT_BALANCE, TURN_NOT_YOURS, TOURNAMENT_NOT_OPEN, MAX_PROPOSALS_EXCEEDED |
| 429 | Too Many Requests - Rate limit exceeded | RATE_LIMIT_EXCEEDED |
| 500 | Internal Server Error - Unexpected failure | INTERNAL_ERROR |
| 503 | Service Unavailable - Maintenance/overload | SERVICE_UNAVAILABLE, DATABASE_UNAVAILABLE |

**Exception Handler**:

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    // API requests get JSON responses
    if ($request->is('api/*')) {
        $correlationId = (string) Str::uuid();
        Log::error('API Error', [
            'correlation_id' => $correlationId,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
        ]);
        
        return match (true) {
            $exception instanceof ValidationException => $this->validationError($exception, $correlationId),
            $exception instanceof AuthenticationException => $this->authError($exception, $correlationId),
            $exception instanceof AuthorizationException => $this->forbiddenError($exception, $correlationId),
            $exception instanceof ModelNotFoundException => $this->notFoundError($exception, $correlationId),
            $exception instanceof BusinessRuleException => $this->businessRuleError($exception, $correlationId),
            $exception instanceof ThrottleRequestsException => $this->rateLimitError($exception, $correlationId),
            default => $this->serverError($exception, $correlationId),
        };
    }
    
    return parent::render($request, $exception);
}

protected function validationError(ValidationException $exception, string $correlationId)
{
    return response()->json([
        'error' => 'VALIDATION_ERROR',
        'message' => 'The given data was invalid',
        'correlation_id' => $correlationId,
        'errors' => collect($exception->errors())->map(fn($messages, $field) => [
            'field' => $field,
            'value' => request($field),
            'constraint' => is_array($messages) ? $messages[0] : $messages,
        ])->values(),
    ], 422);
}

protected function businessRuleError(BusinessRuleException $exception, string $correlationId)
{
    return response()->json([
        'error' => $exception->getCode() ?: 'BUSINESS_RULE_VIOLATION',
        'message' => $exception->getMessage(),
        'correlation_id' => $correlationId,
    ], 422);
}
```

**Custom Business Rule Exceptions**:

```php
// app/Exceptions/BusinessRuleException.php
class BusinessRuleException extends Exception
{
    public static function insufficientBalance(): self
    {
        return new self('INSUFFICIENT_BALANCE', 'Your balance is too low for this operation', 422);
    }
    
    public static function notYourTurn(): self
    {
        return new self('TURN_NOT_YOURS', 'It is not your turn to play', 422);
    }
    
    public static function lobbyFull(): self
    {
        return new self('LOBBY_FULL', 'This lobby has no available seats', 409);
    }
    
    public static function maxProposalsExceeded(): self
    {
        return new self('MAX_PROPOSALS_EXCEEDED', 'You can only have 5 pending proposals at a time', 422);
    }
}
```

---

### Phase 5: Real-Time Sync with Laravel Reverb

**Objective**: Implement WebSocket broadcasting using Laravel Reverb, Redis pub/sub, and Laravel Echo clients.

**Reverb Configuration**:

```php
// config/broadcasting.php
'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST', '0.0.0.0'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
            'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
        ],
        'client_options' => [
            // Optional client config
        ],
    ],
],

// Redis pub/sub for horizontal scaling
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
],
```

**Channel Authorization**:

```php
// routes/channels.php
use App\Models\Game;
use App\Models\Lobby;
use App\Models\User;

// Private game channel - only players can listen
Broadcast::channel('game.{ulid}', function (User $user, string $ulid) {
    $game = Game::where('ulid', $ulid)->firstOrFail();
    return $game->players()->where('user_id', $user->id)->exists();
});

// Private lobby channel - only participants can listen
Broadcast::channel('lobby.{ulid}', function (User $user, string $ulid) {
    $lobby = Lobby::where('ulid', $ulid)->firstOrFail();
    return $lobby->players()->where('user_id', $user->id)->exists();
});

// Private user channel - only user can listen
Broadcast::channel('user.{id}', function (User $user, int $id) {
    return $user->id === (int) $id;
});

// Presence channel for lobby participants
Broadcast::channel('lobby-presence.{ulid}', function (User $user, string $ulid) {
    $lobby = Lobby::where('ulid', $ulid)->firstOrFail();
    if ($lobby->players()->where('user_id', $user->id)->exists()) {
        return ['id' => $user->id, 'username' => $user->username, 'avatar' => $user->avatar];
    }
});

// Public tournament channel - anyone can listen
Broadcast::channel('tournament.{ulid}', function () {
    return true;
});

// Public leaderboard channel - anyone can listen
Broadcast::channel('leaderboard.{gameTitle}', function () {
    return true;
});
```

**Event Broadcasting**:

```php
// app/Events/GameActionExecuted.php
class GameActionExecuted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public Game $game,
        public Action $action,
        public int $sequenceId,
    ) {}
    
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("game.{$this->game->ulid}"),
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'action.executed';
    }
    
    public function broadcastWith(): array
    {
        return [
            'sequence_id' => $this->sequenceId,
            'action' => [
                'id' => $this->action->id,
                'ulid' => $this->action->ulid,
                'user_id' => $this->action->user_id,
                'type' => $this->action->type,
                'data' => $this->action->data,
                'created_at' => $this->action->created_at->toIso8601String(),
            ],
            'game_state_patch' => $this->game->generateStatePatch(), // JSON patch
            'next_player_id' => $this->game->current_player_id,
            'turn_number' => $this->game->turn_number,
        ];
    }
}

// app/Events/LobbyPlayerJoined.php
class LobbyPlayerJoined implements ShouldBroadcast
{
    public function __construct(
        public Lobby $lobby,
        public User $user,
        public int $position,
    ) {}
    
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("lobby-presence.{$this->lobby->ulid}"),
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'player.joined';
    }
    
    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'avatar' => $this->user->avatar,
            ],
            'position' => $this->position,
            'player_count' => $this->lobby->players()->count(),
            'max_players' => $this->lobby->max_players,
        ];
    }
}
```

**Laravel Echo Client Setup** (in @gamerprotocol/ui package):

```javascript
// gamerprotocol-ui/src/echo.ts
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/api/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${getAuthToken()}`,
        },
    },
});

// Game channel subscription
export function subscribeToGame(gameUlid: string, callbacks: {
    onAction?: (data: any) => void;
    onTurnChanged?: (data: any) => void;
    onGameCompleted?: (data: any) => void;
}) {
    return echo.private(`game.${gameUlid}`)
        .listen('.action.executed', callbacks.onAction)
        .listen('.turn.changed', callbacks.onTurnChanged)
        .listen('.game.completed', callbacks.onGameCompleted)
        .error((error) => {
            console.error('WebSocket error:', error);
        });
}

// Lobby presence channel
export function subscribeToLobby(lobbyUlid: string, callbacks: {
    onPlayerJoined?: (user: any) => void;
    onPlayerLeft?: (user: any) => void;
    onReadyStateChanged?: (data: any) => void;
}) {
    return echo.join(`lobby-presence.${lobbyUlid}`)
        .here((users) => {
            console.log('Currently in lobby:', users);
        })
        .joining((user) => {
            console.log('User joined:', user);
            callbacks.onPlayerJoined?.(user);
        })
        .leaving((user) => {
            console.log('User left:', user);
            callbacks.onPlayerLeft?.(user);
        })
        .listen('.ready.changed', callbacks.onReadyStateChanged);
}

// Auto-reconnect with exponential backoff
echo.connector.pusher.connection.bind('disconnected', () => {
    console.log('WebSocket disconnected, will auto-reconnect');
});

echo.connector.pusher.connection.bind('connected', () => {
    console.log('WebSocket connected');
});
```

**Catch-Up Sync Endpoint** (for missed events):

```php
// app/Http/Controllers/Api/V1/Games/GameSyncController.php
public function sync(Request $request, string $ulid)
{
    $request->validate([
        'since_sequence_id' => 'required|integer|min:0',
    ]);
    
    $game = Game::where('ulid', $ulid)->firstOrFail();
    $this->authorize('view', $game);
    
    $sinceSequenceId = $request->input('since_sequence_id');
    
    // Get all actions since the provided sequence ID
    $missedActions = $game->actions()
        ->where('sequence_id', '>', $sinceSequenceId)
        ->orderBy('sequence_id')
        ->get();
    
    return response()->json([
        'current_sequence_id' => $game->current_sequence_id,
        'missed_actions' => $missedActions->map(fn($action) => [
            'sequence_id' => $action->sequence_id,
            'action' => $action->only(['id', 'ulid', 'user_id', 'type', 'data', 'created_at']),
            'state_patch' => $action->state_patch,
        ]),
        'full_state' => $missedActions->count() > 10 ? $game->game_state : null, // Full state if too many missed
    ]);
}
```

**Redis Pub/Sub Configuration** (for multi-server scaling):

```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
    
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
    
    'idempotency' => [
        'url' => env('REDIS_IDEMPOTENCY_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_IDEMPOTENCY_DB', '2'),
    ],
],
```

---

### Database Design

**No schema changes required for Phase 1 & 2**. Controller reorganization is purely API structure refactoring.

**Future migrations** (already documented in data-model.md):
- MatchmakingSignals table (Floor namespace)
- Proposals table (Floor namespace, rename from rematch_requests)
- Tournaments + tournament_user tables (Competitions namespace)
- PlanAudits table (Economy namespace)

---

### Technology Stack

**Backend**:
- Laravel 12.10 (RESTful API framework)
- Laravel Sanctum 4.2 (Token authentication)
- Laravel Cashier 16.0 (Subscription management)
- Laravel Reverb 1.6 (SSE/WebSocket support)
- Spatie Laravel Data 4.5 (DTOs)

**Storage**:
- MySQL 8.0+ (Primary database)
- Redis (Cache + SSE pub/sub)

**Testing**:
- Pest 4.1 + Pest Plugin Laravel 4.0
- Test structure mirrors controller namespaces

---

## Success Criteria *(mandatory)*
