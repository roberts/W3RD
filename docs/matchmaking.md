# ♟️ Multiplayer Architecture: Quickplay & Lobbies

This document details the architecture for the platform's multiplayer systems, designed to be fast, flexible, and engaging. It covers two main components: the **Quickplay** system for automated public matchmaking and the **Lobby** system for user-created private and public games.

---

## 1. Quickplay (Public Matchmaking)

The Quickplay system is designed to get players into a game against a suitable opponent as quickly as possible.

### 1.1. Core Concepts & Technology

*   **Primary Goal:** Automatically pair users with suitable opponents (human or AI) quickly, while preventing repeat matchups and providing a seamless user experience.
*   **Core Technology:** **Redis**. Its speed is essential for this real-time feature. We will use several data structures:
    1.  **Sorted Sets:** For the main queue, ordered by player level. (`quickplay:{game_title}:{game_mode}` where game_mode is optional).
    2.  **Hashes:** To store user join timestamps (`quickplay:timestamps`) and to manage match confirmations (`quickplay:confirm:{match_id}`).
    3.  **Lists:** To maintain a short-term memory of a user's recent opponents (`recent_opponents:{user_id}`).
    4.  **Keys with TTL:** For temporary cooldown penalties (`cooldown:quickplay:{user_id}`).

### 1.2. The Quickplay Flow

This flow is orchestrated by a frequently-run scheduled job (`ProcessQuickplayQueue`).

#### Step 1: User Enters the Queue

*   **Endpoint:** `POST /v1/games/quickplay`
*   **Body:** `{ "game_title": "chess", "game_mode": "blitz" }` (game_mode is optional).
*   **Logic:** The user is added to the appropriate Redis sorted set, and their join time is recorded.

#### Step 2: The Matchmaking Job Executes

The job runs every 5-10 seconds, finds a suitable opponent (human or AI), and avoids recent opponents.

*   **AI Fallback:** If a player waits over 30 seconds, the system seeks an AI opponent via `SchedulingService->findAvailableAgent()`.

#### Step 3: "Accept Game" Confirmation Flow

1.  **Game Found Event:** The job dispatches a `GameFound` event to both users.
2.  **Confirmation:** Users accept by sending a request to `POST /v1/games/quickplay/accept`.
3.  **Game Creation:** Once both users accept, the `Game` record is created, and a `GameStarted` event is broadcast.
4.  **Dodge Penalty:** Failing to accept results in a temporary cooldown that prevents re-queuing. The penalty escalates for repeat offenses (30s, 2m, 5m).

---

## 2. Lobby System (Private & Public Games)

The Lobby system provides a persistent, flexible way for players to organize their own games.

### 2.1. Core Concepts & Technology

*   **Primary Goal:** Allow users to create, schedule, and manage private (invite-only) and public (discoverable) game sessions.
*   **Core Technology:** **Database (MySQL/PostgreSQL)**. Persistence is key, as lobbies can exist for minutes, hours, or days.
*   **Database Schema:**
    *   `lobbies`: Stores the core lobby data (host, game title, mode, public/private status, schedule, etc.).
    *   `lobby_players`: A pivot table linking users to lobbies and tracking their acceptance status (`pending`, `accepted`, `declined`).

### 2.2. The Lobby Flow

#### Step 1: Lobby Creation

*   **Endpoint:** `POST /v1/games/lobbies`
*   **Body:** Includes `game_title`, `game_mode` (optional), `is_public`, `min_players`, `scheduled_at` (optional), and a list of `invitees` for private lobbies.
*   **Logic:** Creates the `Lobby` and `LobbyPlayer` records. For private lobbies, a `LobbyInvitation` event is sent to each invitee.

#### Step 2: Managing and Joining a Lobby

*   **List Public Lobbies:** `GET /v1/games/lobbies`
*   **View a Lobby:** `GET /v1/games/lobbies/{lobby_ulid}`
*   **Respond to Invite:** `PUT /v1/games/lobbies/{lobby_ulid}/players/{user_id}` with a body of `{ "status": "accepted" }`.
*   **Host Management:** The host can kick players (`DELETE /.../players/{user_id}`) and initiate a ready check (`POST /.../ready-check`).

#### Step 3: Game Start (The Handoff)

The system waits for the start conditions to be met.

*   **For Immediate Games:** When the `min_players` count is met by accepted players, the system creates the `Game` record.
*   **For Scheduled Games:** A scheduled job (`ProcessScheduledLobbies`) checks at the `scheduled_at` time. If conditions are met, it creates the `Game`.
*   **Handoff:** In both cases, a `GameStarted` event is broadcast to all players, navigating them to the game screen. The lobby's status is marked as `completed`.

