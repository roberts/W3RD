# ♟️ Multiplayer Architecture: Matchmaking Systems

This document provides comprehensive details on the platform's multiplayer matchmaking systems. It covers three interconnected components: the **Queue System** for automated matchmaking, the **Lobby System** for user-created games, and **Queue-to-Lobby Filling** for hybrid matching.

---

## 1. Queue System (Automated Matchmaking)

The Queue System enables rapid automated matchmaking between players or players and AI agents.

### 1.1. Core Concepts & Technology

**Primary Goal:** Automatically pair players with suitable opponents (human or AI) as quickly as possible, while preventing repeat matchups within the last 3 games.

**Core Technology:** **Database + Redis Hybrid**
- **Database (`queue_slots` table):** Persistent queue entries with user, game title, mode, skill rating, preferences
- **Redis Lists:** Recent opponent tracking (`recent_opponents:{user_id}` - stores last 3 opponent IDs)
- **Redis TTL Keys:** Cooldown penalties for dodging (`cooldown:queue:{user_id}`)

**Key Models:**
- `QueueSlot`: Database record for each queue entry (status: active, matched, cancelled, expired)
- `Mode`: Foreign key relationship - each queue slot targets specific game title + mode

### 1.2. Queue Flow

#### Step 1: User Enters Queue

**Endpoint:** `POST /api/v1/matchmaking/queue`

**Request Body:**
```json
{
  "game_title": "connect-four",
  "mode_id": 1,
  "skill_rating": 1500,
  "preferences": {}
}
```

**Logic:**
1. Validates user is not already in queue or in active game
2. Checks for cooldown penalty
3. Creates `QueueSlot` record with status `active`
4. Returns queue slot ULID for client tracking

#### Step 2: Matchmaking Job Execution

**Job:** `ProcessMatchmakingQueue` (runs every 10 seconds)

**Matching Algorithm:**
1. Groups queue by game title + mode
2. For each player, finds potential opponents:
   - Same title + mode
   - Skill rating within tolerance (±5 levels)
   - Not in player's recent opponents list (last 3 games)
   - Not already removed from queue
3. Creates match when suitable pair found
4. Updates `recent_opponents:{user_id}` Redis lists for both players
5. Broadcasts `GameFound` event to both players
6. Immediately creates game via `GameBuilder`

**AI Fallback:** After 20 seconds in queue without match, system attempts to match with available AI agent via `AgentSchedulingService`

**Agent Selection:**
- Respects agent availability hours
- Filters out recent agent opponents (last 3 games)
- Prefers time-specific agents over 24/7 agents
- Falls back to any agent if all are recent opponents

#### Step 3: Queue Cancellation

**Endpoint:** `DELETE /api/v1/matchmaking/queue/{slot:ulid}`

**Logic:**
- Validates user owns the queue slot
- Marks slot as `cancelled`
- Removes from active matching pool

### 1.3. Dodge Penalties

**Trigger:** Player enters queue multiple times and leaves before match completes

**Penalty System:**
- First dodge: 30 second cooldown
- Second dodge: 2 minute cooldown
- Third+ dodge: 5 minute cooldown
- Cooldowns reset after successful game completion

---

## 2. Lobby System (User-Created Games)

The Lobby System provides a flexible way for players to create, coordinate, and manage custom game sessions.

### 2.1. Core Concepts & Technology

**Primary Goal:** Enable users to create private (invite-only) or public (discoverable) games with full host control and scheduling capabilities.

**Core Technology:** **Database (MySQL/PostgreSQL)**

**Database Schema:**
- `lobbies`: Core lobby data (host, game title, mode, public/private, scheduling, status)
- `lobby_players`: Pivot table linking users to lobbies (status: pending, accepted, declined; source: host, invited, public_join, queue_matched)
- `modes`: Foreign key relationship for game configuration

**Lobby States:**
- `pending`: Waiting for players
- `ready`: All players ready, can start
- `starting`: Game creation in progress
- `completed`: Game created successfully
- `cancelled`: Lobby disbanded

**Player Sources:**
- `host`: User who created the lobby (auto-accepted)
- `invited`: User sent explicit invitation
- `public_join`: User joined public lobby manually
- `queue_matched`: User assigned from queue system

### 2.2. Lobby Flow

#### Step 1: Lobby Creation

**Endpoint:** `POST /api/v1/matchmaking/lobbies`

**Request Body:**
```json
{
  "game_title": "hearts",
  "mode_id": 2,
  "is_public": true,
  "min_players": 4,
  "scheduled_at": "2025-11-22T20:00:00Z",
  "invitees": [1, 2, 3]
}
```

**Logic:**
1. Creates `Lobby` record with host as creator
2. Adds host as first `LobbyPlayer` (status: accepted, source: host)
3. For each invitee, creates `LobbyPlayer` (status: pending, source: invited)
4. Broadcasts `LobbyInvitation` event to each invitee
5. Returns lobby ULID

#### Step 2: Lobby Discovery & Joining

**List Public Lobbies:** `GET /api/v1/matchmaking/lobbies`
- Returns all public, non-scheduled, pending lobbies
- Filters by game title/mode (optional query params)
- Pagination support

**View Specific Lobby:** `GET /api/v1/matchmaking/lobbies/{lobby_ulid}`
- Returns detailed lobby info including players and their statuses
- Available to invited players and public lobbies

**Join Public Lobby:** `PUT /api/v1/matchmaking/lobbies/{lobby_ulid}/players/{username}`
```json
{
  "status": "accepted"
}
```
- For public lobbies: Auto-accepts and adds player (source: public_join)
- For invited players: Updates existing pending record to accepted
- Validates lobby not full
- Broadcasts `LobbyPlayerJoined` event

#### Step 3: Host Management

**Invite Players:** `POST /api/v1/matchmaking/lobbies/{lobby_ulid}/players`
```json
{
  "user_ids": [4, 5]
}
```

**Kick Player:** `DELETE /api/v1/matchmaking/lobbies/{lobby_ulid}/players/{username}`
- Host-only action
- Cannot kick self
- Removes player from lobby

**Ready Check:** `POST /api/v1/matchmaking/lobbies/{lobby_ulid}/ready-check`
- Host initiates ready check
- All players must confirm ready status
- Rate limited to prevent spam

**Cancel Lobby:** `DELETE /api/v1/matchmaking/lobbies/{lobby_ulid}`
- Host-only action
- Marks lobby as cancelled
- Notifies all players

#### Step 4: Game Start Conditions

**Immediate Start (< 60 seconds old):**
- When `min_players` count met by accepted players
- All players have status `accepted`
- Game creates immediately via `LobbyGameStarter`

**Ready Check Flow (60+ seconds old):**
- Host must explicitly trigger ready check
- Prevents instant-start spam for older lobbies
- After ready check passes, game starts

**Scheduled Games:**
- `ProcessScheduledLobbies` job runs every minute
- Checks if current time >= `scheduled_at`
- Validates player count and acceptance status
- Creates game if conditions met

**Auto-Start from Queue Fill:**
- When lobby filled entirely from queue (see section 3)
- Bypasses ready check if lobby < 60 seconds old
- Creates game immediately

---

## 3. Queue-to-Lobby Filling (Hybrid Matching)

This system bridges queue and lobby systems by automatically filling public lobbies with queue players.

### 3.1. Core Concepts

**Primary Goal:** Reduce wait times for lobby hosts by filling empty slots from the queue pool, while respecting recent opponent rules.

**When It Applies:**
- Public lobbies only (not private/scheduled)
- Lobby status is `pending`
- Lobby has fewer players than required
- Sufficient queue players available for same title/mode

### 3.2. Filling Flow

**Job:** `FillLobbiesFromQueue` (runs every 10 seconds, after `ProcessMatchmakingQueue`)

**Algorithm:**
1. **Find Eligible Lobbies:**
   - Public: `is_public = true`
   - Not scheduled: `scheduled_at IS NULL`
   - Status: `pending`
   - Needs players: current count < required count

2. **Calculate Slots Needed:**
   - Uses game title's player requirements
   - For exact-count games (Connect Four, Hearts): uses `maxPlayers()`
   - For flexible games: uses lobby's `min_players`

3. **Build Exclusion List:**
   - Existing lobby players (can't match yourself)
   - Recent opponents of ALL existing players
   - Queries `recent_opponents:{player_id}` for each lobby player
   - Combines into unique exclusion list

4. **Find Queue Candidates:**
   - Matches title_slug and mode_id
   - Status: `active`
   - User not in exclusion list
   - Limits to exact number needed

5. **Complete-Fill-Only Logic:**
   - Only proceeds if found players >= slots needed
   - Prevents partial fills that might stall

6. **Atomic Assignment (Transaction):**
   ```php
   DB::transaction(function () {
       // For each queue player:
       // 1. Lock queue slot (lockForUpdate)
       // 2. Create LobbyPlayer (status: accepted, source: queue_matched)
       // 3. Update QueueSlot (status: matched, matched_lobby_id)
       // 4. Update lobby status to 'starting'
   });
   ```

7. **Recent Opponent Updates:**
   - For each player in final lobby
   - Add all other players to their `recent_opponents` list
   - Trim lists to last 3 opponents

8. **Auto-Start Decision:**
   - If lobby age < 60 seconds: Auto-start game immediately
   - If lobby age >= 60 seconds: Log warning (should use ready check)

### 3.3. Recent Opponent Logic

**Storage:** Redis Lists (`recent_opponents:{user_id}`)

**Structure:**
```
recent_opponents:123 = [456, 789, 101]  // Last 3 opponent user IDs
```

**Update Timing:**
- After queue match creates game
- After queue fills lobby
- After lobby starts game

**Filtering Logic:**
- 2-player games: Each player tracks 1 opponent per game
- 4-player games (Hearts): Each player tracks 3 opponents per game
- Always maintains last 3 entries regardless of game type

**Fallback:** If ALL queue players are recent opponents, lobby waits for new players (no match made)

---

## 4. Proposal System (Challenges & Rematches)

The Proposal System handles direct challenges between players and rematch requests after games.

### 4.1. Rematch Flow

**Endpoint:** `POST /api/v1/matchmaking/proposals`

**Request Body:**
```json
{
  "type": "rematch",
  "game_id": "01234567890abcdefg",
  "message": "Good game, rematch?"
}
```

**Logic:**
1. Validates requester was in original game
2. Creates `Proposal` record with opponent
3. Broadcasts `ProposalReceived` event
4. Sets 60-second expiration timer

**Accept:** `POST /api/v1/matchmaking/proposals/{ulid}/accept`
- Creates new game with swapped player positions
- Broadcasts `GameStarted` to both players

**Decline:** `POST /api/v1/matchmaking/proposals/{ulid}/decline`
- Marks proposal as declined
- Notifies requester

**Expiration:** `ExpireProposals` job (runs every minute)
- Marks expired proposals after 60 seconds
- Notifies requester of expiration

### 4.2. Direct Challenge Flow

**Endpoint:** `POST /api/v1/matchmaking/proposals`

**Request Body:**
```json
{
  "type": "challenge",
  "opponent_id": 456,
  "game_title": "checkers",
  "mode_id": 3
}
```

**Logic:** Similar to rematch but for any user, not limited to past opponents

---

## 5. API Reference

### Queue Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/v1/matchmaking/queue` | Required | Join matchmaking queue |
| DELETE | `/api/v1/matchmaking/queue/{ulid}` | Required | Leave queue |

### Lobby Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/matchmaking/lobbies` | Required | List public lobbies |
| POST | `/api/v1/matchmaking/lobbies` | Required | Create lobby |
| GET | `/api/v1/matchmaking/lobbies/{ulid}` | Required | View lobby details |
| DELETE | `/api/v1/matchmaking/lobbies/{ulid}` | Required | Cancel lobby (host only) |
| POST | `/api/v1/matchmaking/lobbies/{ulid}/ready-check` | Required | Initiate ready check (host only) |
| POST | `/api/v1/matchmaking/lobbies/{ulid}/players` | Required | Invite players (host only) |
| PUT | `/api/v1/matchmaking/lobbies/{ulid}/players/{username}` | Required | Accept/join lobby |
| DELETE | `/api/v1/matchmaking/lobbies/{ulid}/players/{username}` | Required | Kick player (host only) |

### Proposal Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/v1/matchmaking/proposals` | Required | Create challenge/rematch |
| POST | `/api/v1/matchmaking/proposals/{ulid}/accept` | Required | Accept proposal |
| POST | `/api/v1/matchmaking/proposals/{ulid}/decline` | Required | Decline proposal |

---

## 6. Code Structure

### 6.1. Directory Organization

```
app/
├── Matchmaking/
│   ├── Enums/                    # Status enums and constants
│   │   ├── LobbyPlayerSource.php
│   │   ├── LobbyPlayerStatus.php
│   │   ├── LobbyStatus.php
│   │   ├── ProposalStatus.php
│   │   ├── ProposalType.php
│   │   └── QueueSlotStatus.php
│   ├── Events/                   # Broadcast events
│   │   ├── LobbyInvitation.php
│   │   ├── LobbyPlayerJoined.php
│   │   ├── LobbyReadyCheck.php
│   │   ├── ProposalAccepted.php
│   │   ├── ProposalCancelled.php
│   │   ├── ProposalCreated.php
│   │   ├── ProposalDeclined.php
│   │   ├── ProposalExpired.php
│   │   └── ProposalSent.php
│   ├── Lobby/                    # Lobby-specific logic
│   │   ├── InvitationBroadcaster.php
│   │   ├── LobbyGameStarter.php
│   │   ├── LobbyManager.php
│   │   ├── LobbyPlayerManager.php
│   │   ├── LobbyQueueFiller.php  # NEW: Fills lobbies from queue
│   │   └── LobbyValidator.php
│   ├── Queue/                    # Queue-specific logic
│   │   ├── Actions/
│   │   │   ├── ApplyDodgePenaltyAction.php
│   │   │   ├── JoinQueueAction.php
│   │   │   └── LeaveQueueAction.php
│   │   ├── AgentMatcher.php      # NEW: AI agent matching logic
│   │   ├── MatchConfirmationHandler.php  # NEW: Match confirmation flow
│   │   ├── MatchmakingService.php  # NEW: Core matching algorithm
│   │   ├── OpponentFinder.php    # NEW: Human opponent finding
│   │   ├── QueueManager.php
│   │   ├── RecentOpponentTracker.php  # NEW: Recent opponent tracking
│   │   └── SlotManager.php
│   ├── Proposals/                # Challenge/rematch logic
│   │   ├── ChallengeHandler.php
│   │   ├── ProposalFactory.php
│   │   ├── ProposalHandler.php
│   │   ├── RematchHandler.php
│   │   └── RematchValidator.php
│   ├── Orchestrators/            # High-level coordination
│   │   ├── LobbyOrchestrator.php
│   │   ├── ProposalOrchestrator.php
│   │   └── QueueOrchestrator.php
│   ├── Results/                  # Result objects
│   │   ├── LobbyOperationResult.php
│   │   ├── ProposalResult.php
│   │   └── QueueResult.php
│   └── Shared/                   # Shared utilities
│       └── PlayerAvailabilityChecker.php
├── Jobs/                         # Background jobs (thin orchestrators)
│   ├── AgentAutoAcceptRematch.php   # Agent auto-accept logic
│   ├── CheckAndCancelPendingProposals.php  # Proposal cleanup
│   ├── ExpireProposals.php       # Expired proposal cleanup
│   ├── FillLobbiesFromQueue.php  # Delegates to LobbyQueueFiller
│   ├── ProcessMatchmakingQueue.php  # Delegates to MatchmakingService
│   └── ProcessScheduledLobbies.php  # Scheduled game starts
├── Services/Matchmaking/         # HTTP response mapping
│   ├── LobbyQueryService.php
│   ├── LobbyResponseMapper.php
│   ├── ProposalResponseMapper.php
│   └── QueueResponseMapper.php
└── Http/Controllers/Api/V1/Matchmaking/
    ├── LobbyController.php       # Lobby HTTP endpoints
    ├── QueueController.php       # Queue HTTP endpoints
    └── ProposalController.php    # Proposal HTTP endpoints
```

### 6.2. Key Classes

#### Orchestrators (Coordination Layer)

**`LobbyOrchestrator`**: High-level lobby operations
- `createLobby()`: Creates lobby with host and invitees
- `cancelLobby()`: Cancels lobby and notifies players
- `invitePlayer()`: Handles player invitations
- `acceptInvitationOrJoin()`: Handles accept/decline/join
- `kickPlayer()`: Removes players from lobby
- Coordinates between Manager, Validator, and PlayerManager classes

**`QueueOrchestrator`**: Queue entry coordination
- `joinQueue()`: Validates and creates queue slot
- `cancelQueue()`: Removes user from queue
- Integrates cooldown checks and validation

**`ProposalOrchestrator`**: Proposal lifecycle management
- `createProposal()`: Creates rematch/challenge proposal
- `acceptProposal()`: Handles acceptance and game creation
- `declineProposal()`: Handles rejection
- `expireOldProposals()`: Cleans up expired proposals

#### Core Services (Business Logic Layer)

**`MatchmakingService`**: Core queue processing logic
- `processQueue()`: Main matchmaking algorithm
- Coordinates opponent finding, AI fallback, and match confirmation
- Parses queue data and orchestrates matching flow

**`OpponentFinder`**: Human opponent matching
- `findOpponent()`: Finds suitable human opponents
- Applies skill range filtering (±5 levels)
- Respects recent opponent history (last 3 games)
- `getWaitTime()`: Calculates player queue wait time

**`AgentMatcher`**: AI agent fallback matching
- `matchWithAgent()`: Matches player with available AI agent
- Calls `AgentSchedulingService` to find agents
- Creates game via `GameBuilder` when agent found
- Tracks recent agent opponents

**`MatchConfirmationHandler`**: Match confirmation flow
- `createMatchConfirmation()`: Creates confirmation for matched players
- Broadcasts `GameFound` events to both players
- Schedules timeout penalties for non-accepters
- Manages 15-second confirmation window

**`RecentOpponentTracker`**: Recent opponent tracking
- `recordMatch()`: Records two players have matched
- `getRecentOpponents()`: Retrieves last 3 opponents
- Manages Redis lists for each player

**`LobbyQueueFiller`**: Lobby filling from queue
- `tryFillLobby()`: Attempts to fill lobby with queue players
- Respects recent opponent rules for all lobby players
- Only fills if enough players available (all-or-nothing)
- Triggers auto-start for lobbies < 60 seconds old

#### Managers (Entity Management Layer)

**`LobbyManager`**: Core lobby operations
- `createLobby()`: Database record creation
- `cancelLobby()`: Status updates
- `markLobbyReady()`: Ready state management
- `canStartGame()`: Validates start conditions
- `getPlayerIds()`: Returns all player IDs in lobby

**`LobbyPlayerManager`**: Player-lobby relationships
- `invitePlayer()`: Creates invitation
- `inviteMultiplePlayers()`: Bulk invitations
- `acceptInvitation()`: Updates status to accepted
- `declineInvitation()`: Updates status to declined
- `joinPublicLobby()`: Public lobby joining
- `kickPlayer()`: Removes player

**`QueueManager`**: Queue slot lifecycle
- `joinQueue()`: Adds player to queue
- `leaveQueue()`: Removes player from queue
- Delegates to JoinQueueAction and LeaveQueueAction

**`SlotManager`**: Queue slot database operations
- `createSlot()`: Creates QueueSlot record
- `cancelSlot()`: Marks slot as cancelled
- `expireOldSlots()`: Cleans up expired slots

**`LobbyGameStarter`**: Game creation from lobby
- `startGame()`: Calls `GameBuilder` with lobby data
- Handles client_id tracking from lobby_players

#### Validators (Validation Layer)

**`LobbyValidator`**: Business rule validation
- `validateIsHost()`: Host permission checks
- `validateNotFull()`: Player capacity checks
- `validateGameTitle()`: Game availability checks

**`RematchValidator`**: Rematch-specific validation
- Validates original game existence
- Checks player participation
- Prevents duplicate requests

#### Handlers (Specialized Logic)

**`RematchHandler`**: Rematch proposal processing
- Creates proposals from completed games
- Swaps player positions for fairness
- Manages cooldown periods

**`ChallengeHandler`**: Direct challenge processing
- Creates proposals between any users
- Validates challenge parameters

### 6.3. Job Scheduling

**Schedule Definition:** `routes/console.php`

```php
// Queue-to-queue matching (priority: runs first)
Schedule::job(new ProcessMatchmakingQueue)->everyTenSeconds();

// Queue-to-lobby filling (runs after queue matching)
Schedule::job(new FillLobbiesFromQueue)->everyTenSeconds();

// Scheduled game starts
Schedule::job(new ProcessScheduledLobbies)->everyMinute();

// Proposal expiration cleanup
Schedule::job(new ExpireProposals)->everyMinute();
```

**Job Execution Order:**
1. `ProcessMatchmakingQueue`: Delegates to `MatchmakingService` for queue-to-queue matching (highest priority)
2. `FillLobbiesFromQueue`: Delegates to `LobbyQueueFiller` for filling lobbies with remaining queue players
3. `ProcessScheduledLobbies`: Starts scheduled games at their designated times
4. `ExpireProposals`: Delegates to `ProposalOrchestrator` for cleaning up expired proposals

**Job Architecture Pattern:**
- Jobs are **thin orchestrators** that handle scheduling and infrastructure concerns
- Core business logic resides in **domain services** within `app/Matchmaking/`
- This separation enables testing business logic without job infrastructure
- Matches the pattern used in `app/GameEngine/` for consistency

### 6.4. Database Models

**`Lobby`**: Lobby record
- Relationships: `host()`, `players()`, `mode()`
- Casts: `title_slug` to `GameTitle` enum
- Methods: `canStartGame()`, `markAsReady()`, `markAsCancelled()`

**`LobbyPlayer`**: Player-lobby pivot
- Relationships: `lobby()`, `user()`
- Casts: `status` to `LobbyPlayerStatus`, `source` to `LobbyPlayerSource`
- Methods: `accept()`, `decline()`, `isAccepted()`

**`QueueSlot`**: Queue entry record
- Relationships: `user()`, `lobby()` (matched lobby)
- Casts: `status` to `QueueSlotStatus`
- Methods: `isActive()`, `hasExpired()`

**`Mode`**: Game mode configuration
- Relationships: `title()` (via title_slug)
- Contains mode-specific rules and settings

### 6.5. Event Flow

**Queue Match Found (Human vs Human):**
1. `MatchmakingService` delegates to `OpponentFinder` to find match
2. `MatchConfirmationHandler` creates match confirmation
3. Broadcasts `GameFound` event to both players (15-second window)
4. `RecentOpponentTracker` updates recent opponent lists
5. Players accept match via API endpoint
6. Creates `Game` via `GameBuilder`
7. Broadcasts `GameStarted` event

**Queue Match Found (Human vs AI):**
1. `MatchmakingService` detects 20+ second wait time
2. `AgentMatcher` finds available AI agent
3. Creates `Game` via `GameBuilder` immediately
4. `RecentOpponentTracker` updates recent opponent lists
5. Sets player activity states to `IN_GAME`
6. Broadcasts `GameFound` event to human player

**Lobby Filled from Queue:**
1. `LobbyQueueFiller` finds eligible queue players
2. Atomically assigns players in database transaction
3. Updates lobby status to `starting`
4. `RecentOpponentTracker` updates opponent lists for all players
5. Calls `LobbyGameStarter->startGame()`
6. `GameBuilder` creates game from lobby
7. Broadcasts `GameStarted` event

**Manual Lobby Start:**
1. Host triggers ready check OR lobby meets auto-start conditions
2. `LobbyOrchestrator` validates conditions
3. Calls `LobbyGameStarter->startGame()`
4. `GameBuilder` creates game
5. Broadcasts `GameStarted` event

**Proposal (Rematch/Challenge) Flow:**
1. User creates proposal via `ProposalOrchestrator`
2. `RematchHandler` or `ChallengeHandler` validates and creates `Proposal` record
3. Broadcasts `ProposalCreated` event to opponent
4. Opponent accepts/declines via API endpoint
5. If accepted: `GameBuilder` creates new game
6. If declined: Broadcasts `ProposalDeclined` event
7. If expired: `ExpireProposals` job marks expired and broadcasts `ProposalExpired` event

### 6.6. Redis Data Structures

**Recent Opponents:**
```
Key: recent_opponents:{user_id}
Type: List
Value: [opponent_id_1, opponent_id_2, opponent_id_3]
TTL: None (maintained indefinitely)
Management: RecentOpponentTracker service
```

**Queue Tracking:**
```
Key: queue:{game_title}:{mode}
Type: Sorted Set (ZSET)
Score: Skill rating
Value: user_id
Management: JoinQueueAction, LeaveQueueAction
```

```
Key: queue:timestamps
Type: Hash
Field: user_id
Value: join_timestamp
Usage: Calculate wait time for AI fallback
```

```
Key: queue:clients
Type: Hash
Field: user_id
Value: client_id
Usage: Track client_id for game creation
```

**Match Confirmation:**
```
Key: queue:accept:{match_id}
Type: Hash
Fields: user_id_1, user_id_2
Values: "0" (not accepted) or "1" (accepted)
TTL: 15 seconds
Management: MatchConfirmationHandler
```

```
Key: queue:match:{match_id}
Type: Hash
Fields: game_title, game_mode, player_{user_id}_client
TTL: 15 seconds
Usage: Store match metadata for game creation
```

**Cooldown Penalties:**
```
Key: cooldown:queue:{user_id}
Type: String
Value: "1"
TTL: 30s - 5m (based on dodge count)
Management: ApplyDodgePenaltyAction
```

```
Key: queue:offenses:{user_id}
Type: String
Value: offense_count
TTL: 4 hours
Usage: Track escalating dodge penalties
```

**Agent Cooldown:**
```
Key: agent:{user_id}:cooldown
Type: String
TTL: Variable (cleared on rematch acceptance)
Usage: Manage agent availability between games
```

### 6.7. Design Patterns

**Thin Jobs, Rich Domain:**
- Jobs in `app/Jobs/` are thin orchestrators (scheduling, infrastructure)
- Core business logic in `app/Matchmaking/` domain services
- Enables testing logic without job infrastructure
- Matches pattern used in `app/GameEngine/`

**Orchestrator Pattern:**
- Controllers call Orchestrators
- Orchestrators coordinate Services, Managers, Validators, Handlers
- Manages transactions and error handling
- Examples: `LobbyOrchestrator`, `QueueOrchestrator`, `ProposalOrchestrator`

**Service Layer:**
- Domain services contain core algorithms and workflows
- Examples: `MatchmakingService`, `OpponentFinder`, `AgentMatcher`
- Focused, testable, reusable business logic
- No direct HTTP or job knowledge

**Manager Pattern:**
- Focused entity management units
- Single responsibility (Lobby, Player, Queue, Slot)
- Handle database operations and state changes
- Examples: `LobbyManager`, `QueueManager`, `SlotManager`

**Handler Pattern:**
- Specialized logic for specific proposal types
- Implements `ProposalHandler` interface
- Examples: `RematchHandler`, `ChallengeHandler`
- Factory pattern via `ProposalFactory`

**Result Objects:**
- `LobbyOperationResult`, `QueueResult`, `ProposalResult`
- Encapsulate success/failure with context
- Consistent error handling across endpoints
- Enables testing without HTTP layer

**Action Pattern:**
- Atomic, focused operations in `Queue/Actions/`
- Single responsibility per action
- Examples: `JoinQueueAction`, `ApplyDodgePenaltyAction`
- Composable and testable

**Event Broadcasting:**
- Decoupled real-time notifications
- WebSocket/Pusher integration via Laravel Broadcasting
- Events in `app/Matchmaking/Events/`
- Clients subscribe to lobby/user channels

**Dependency Injection:**
- Services injected via constructor
- Enables testing with mocks
- Laravel's service container handles resolution
- Optional constructor parameters for job testability

