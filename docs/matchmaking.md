# ♟️ Public Matchmaking Architecture

This document details the architecture for an intelligent public matchmaking queue, designed to be fast, fair, and engaging.

---

## 1. Core Concepts & Technology

*   **Primary Goal:** Automatically pair users with suitable opponents (human or AI) quickly, while preventing repeat matchups and providing a seamless user experience.
*   **Core Technology:** **Redis**. Its speed is essential for this real-time feature. We will use three main data structures:
    1.  **Sorted Sets:** For the main queue, ordered by player level. (`matchmaking:{title_slug}`)
    2.  **Hashes:** To store the timestamp when a user joins the queue. (`matchmaking:timestamps`)
    3.  **Lists:** To maintain a short-term memory of a user's recent opponents. (`recent_opponents:{user_id}`)

---

## 2. The Matchmaking Flow

This flow is orchestrated by a frequently-run scheduled job (`ProcessMatchmakingQueue`).

### Step 1: User Enters the Queue

*   **Endpoint:** `POST /v1/matchmaking/queue`
*   **Body:** `{ "title_slug": "validate-four" }`
*   **Logic:**
    1.  The user's `level` for the specified game title is retrieved.
    2.  The user is added to the game title's sorted set: `ZADD matchmaking:validate-four <user_level> <user_id>`
    3.  The user's join time is recorded: `HSET matchmaking:timestamps <user_id> <current_timestamp>`

### Step 2: The Matchmaking Job Executes

The job runs every 5-10 seconds and performs the following logic for each game title queue.

1.  **Get a Player:** Fetch the player who has been waiting the longest (`PlayerA`).
2.  **Check Wait Time:** Retrieve `PlayerA`'s join timestamp.
3.  **Agent Fallback Logic (Wait > 30s):**
    *   If `PlayerA` has been waiting over 30 seconds, the system stops looking for human players and immediately seeks an AI opponent.
    *   It calls `SchedulingService->findAvailableAgent()`, passing a list of excluded user IDs (the player's own ID and their recent opponents).
    *   If a suitable agent is found, a game is created. The agent's `User` model is used, making it indistinguishable from a human player to the client.
    *   If no agent is free, `PlayerA` remains in the queue for the next cycle.
4.  **Human Matchmaking Logic (Wait < 30s):**
    *   The system fetches `PlayerA`'s recent opponents list from Redis: `LRANGE recent_opponents:{playerA_id} 0 -1`.
    *   It then searches the queue for another player (`PlayerB`) with a similar level.
    *   **Validation:** It checks if `PlayerB` is on `PlayerA`'s recent list, and vice-versa. If there's a conflict, it discards `PlayerB` and looks for the next candidate.
    *   If a valid human opponent is found, they proceed to the "Accept Game" flow.

### Step 3: "Accept Game" Confirmation Flow

To prevent AFK players from starting games, a confirmation step is required.

1.  **Game Found Event:** When a valid pair is identified, the job does **not** create the game. Instead, it dispatches a `GameFound` event via Reverb to both users.
2.  **Client-Side Prompt:** Both clients receive the event and display an "Accept Game" prompt with a 10-second countdown.
3.  **Confirmation:** If a user accepts, the client sends a request to a new endpoint: `POST /v1/matchmaking/accept`. The server uses Redis to track that the user has accepted.
4.  **Game Creation:** Once the server has received acceptances from **both** users, it finalizes the process:
    *   Creates the `Game` record in the database.
    *   Removes both users from the matchmaking queue.
    *   Updates their `recent_opponents` lists in Redis.
    *   Broadcasts a `GameCreated` event with the game details, navigating the clients to the game screen.
5.  **Failure/Decline:** If one user declines or their 10-second timer expires, they are removed from the queue. The other user is placed back at the front of the queue to find a new opponent on the next job cycle.

---

## 3. Queue Dodge Penalty

To discourage players from repeatedly declining games to "fish" for a preferred opponent, a penalty system is implemented.

*   **Concept:** A user who declines a game or fails to accept in time receives a temporary matchmaking cooldown.
*   **Implementation:**
    *   A Redis key with a TTL (Time To Live) is used (e.g., `cooldown:matchmaking:{user_id}`).
    *   When a user "dodges," the server sets this key with an expiry (e.g., 60 seconds).
    *   The `POST /v1/matchmaking/queue` endpoint will first check if this key exists. If it does, the request is rejected with a "You are on a matchmaking cooldown" message until the key expires.
    *   The cooldown duration can be increased for repeat offenders.
