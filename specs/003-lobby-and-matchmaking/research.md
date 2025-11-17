# Research: Lobby and Matchmaking System

**Purpose**: To resolve unknowns and define best practices for the implementation of the lobby and matchmaking features.

## 1. Redis Data Structures for Matchmaking

### Decision
We will use a combination of Redis Sorted Sets, Hashes, and simple Keys with TTLs to manage the public matchmaking flow.

*   **Queue (`matchmaking:{game_title}`):** A **Sorted Set** will be used for the main queue.
    *   **Score**: Player's skill level.
    *   **Value**: `user_id`.
    *   **Rationale**: This allows for efficient, ordered retrieval of players based on skill, making it easy to find similarly-skilled opponents.
*   **Timestamps (`matchmaking:timestamps`):** A **Hash** will store the timestamp when a user joins the queue.
    *   **Field**: `user_id`.
    *   **Value**: `join_timestamp`.
    *   **Rationale**: This is required for the AI fallback logic (checking wait time > 30s). A single hash is more memory-efficient than many individual keys.
*   **Recent Opponents (`recent_opponents:{user_id}`):** A **List** will maintain a short-term memory of recent opponents.
    *   **Rationale**: `LPUSH` and `LTRIM` provide a simple, capped-size list to prevent repeat matchups.
*   **Confirmations (`matchmaking:confirm:{match_id}`):** A **Hash** with a 15-second TTL will track the "Accept Game" state.
    *   **Field**: `user_id`.
    *   **Value**: `1` for accepted, `0` for pending.
    *   **Rationale**: A temporary hash is perfect for this transient state. It's atomic and automatically cleans itself up via TTL, preventing stale data.
*   **Cooldowns (`cooldown:matchmaking:{user_id}`):** A simple **Key** with a dynamic TTL.
    *   **Rationale**: The simplest and most direct way to implement a temporary flag. `EXISTS` is a very fast check.

### Alternatives Considered
*   **Using only Lists:** We could use lists for the queue, but this would not allow for skill-based ordering, making fair matchmaking difficult.
*   **Database for Confirmations:** Using the database for the 10-second confirmation step would introduce unnecessary latency and table churn for a highly transient operation. Redis is superior for this task.

## 2. Real-Time Event Broadcasting with Laravel Reverb

### Decision
We will use Laravel Reverb to broadcast all real-time events to the frontend. All events will implement the `ShouldBroadcast` interface.

*   **`GameFound` (Public Matchmaking):**
    *   **Channel**: Private channel for each user (`App.Models.User.{id}`).
    *   **Payload**: Details of the proposed match.
*   **`LobbyInvitation` (Private Lobbies):**
    *   **Channel**: Private channel for the invited user (`App.Models.User.{id}`).
    *   **Payload**: Lobby details (host, game, etc.).
*   **`LobbyReadyCheck` (Lobbies):**
    *   **Channel**: Private channel for the lobby (`Lobby.{lobby_id}`).
    *   **Payload**: A simple event to trigger the UI prompt.
*   **`GameStarted` (Both Systems):**
    *   **Channel**: Private channel for the game (`Game.{game_id}`).
    *   **Payload**: Full game state to navigate clients to the game screen.

### Alternatives Considered
*   **Frontend Polling:** The client could repeatedly poll API endpoints to check for status updates. This is inefficient, creates significant server load, and does not provide a true real-time experience. Reverb (WebSockets) is the industry standard and correct tool for this.
