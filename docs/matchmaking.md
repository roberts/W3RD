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
│   │   └── QueueSlotStatus.php
│   ├── Events/                   # Broadcast events
│   │   ├── LobbyInvitation.php
│   │   ├── LobbyPlayerJoined.php
│   │   └── ProposalReceived.php
│   ├── Lobby/                    # Lobby-specific logic
│   │   ├── InvitationBroadcaster.php
│   │   ├── LobbyGameStarter.php
│   │   ├── LobbyManager.php
│   │   ├── LobbyPlayerManager.php
│   │   └── LobbyValidator.php
│   ├── Queue/                    # Queue-specific logic
│   │   ├── Actions/
│   │   │   ├── ApplyDodgePenaltyAction.php
│   │   │   └── ValidateQueueEntryAction.php
│   │   ├── QueueManager.php
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
│   └── Results/                  # Result objects
│       └── LobbyOperationResult.php
├── Jobs/                         # Background jobs
│   ├── FillLobbiesFromQueue.php  # Fills lobbies from queue
│   ├── ProcessMatchmakingQueue.php # Queue-to-queue matching
│   ├── ProcessScheduledLobbies.php # Scheduled game starts
│   └── ExpireProposals.php       # Cleans up old proposals
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
- `updatePlayerStatus()`: Handles accept/decline/join
- Coordinates between Manager, Validator, and PlayerManager classes

**`QueueOrchestrator`**: Queue entry coordination
- `joinQueue()`: Validates and creates queue slot
- `leaveQueue()`: Cancels queue slot
- Integrates cooldown checks and validation

**`ProposalOrchestrator`**: Proposal lifecycle management
- `createRematch()`: Creates rematch proposal
- `acceptProposal()`: Handles acceptance and game creation
- `declineProposal()`: Handles rejection

#### Managers (Business Logic Layer)

**`LobbyManager`**: Core lobby operations
- `createLobby()`: Database record creation
- `cancelLobby()`: Status updates
- `markLobbyReady()`: Ready state management
- `canStartGame()`: Validates start conditions

**`LobbyPlayerManager`**: Player-lobby relationships
- `invitePlayer()`: Creates invitation
- `acceptInvitation()`: Updates status
- `joinPublicLobby()`: Public lobby joining
- `kickPlayer()`: Removes player

**`QueueManager`**: Queue slot lifecycle
- Validates entry conditions
- Manages slot creation/cancellation
- Tracks queue statistics

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
1. `ProcessMatchmakingQueue`: Matches queue players with each other (highest priority)
2. `FillLobbiesFromQueue`: Fills lobbies with remaining queue players
3. `ProcessScheduledLobbies`: Starts scheduled games at their designated times
4. `ExpireProposals`: Cleans up expired rematch/challenge proposals

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

**Queue Match Found:**
1. `ProcessMatchmakingQueue` finds match
2. Creates `Game` via `GameBuilder`
3. Broadcasts `GameStarted` event
4. Updates `recent_opponents` Redis lists

**Lobby Filled from Queue:**
1. `FillLobbiesFromQueue` assigns queue players
2. Updates lobby status to `starting`
3. Calls `LobbyGameStarter->startGame()`
4. `GameBuilder` creates game from lobby
5. Broadcasts `GameStarted` event

**Manual Lobby Start:**
1. Host triggers ready check OR lobby meets auto-start conditions
2. `LobbyOrchestrator` validates conditions
3. Calls `LobbyGameStarter->startGame()`
4. `GameBuilder` creates game
5. Broadcasts `GameStarted` event

### 6.6. Redis Data Structures

**Recent Opponents:**
```
Key: recent_opponents:{user_id}
Type: List
Value: [opponent_id_1, opponent_id_2, opponent_id_3]
TTL: None (maintained indefinitely)
```

**Cooldown Penalties:**
```
Key: cooldown:queue:{user_id}
Type: String
Value: timestamp
TTL: 30s - 5m (based on dodge count)
```

### 6.7. Design Patterns

**Orchestrator Pattern:**
- Controllers call Orchestrators
- Orchestrators coordinate Managers, Validators, Handlers
- Manages transactions and error handling

**Manager Pattern:**
- Focused business logic units
- Single responsibility (Lobby, Player, Queue)
- No direct HTTP or event knowledge

**Result Objects:**
- `LobbyOperationResult` encapsulates success/failure
- Consistent error handling across endpoints
- Enables testing without HTTP layer

**Event Broadcasting:**
- Decoupled real-time notifications
- WebSocket/Pusher integration
- Clients subscribe to lobby/user channels

