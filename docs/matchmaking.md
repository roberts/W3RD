# ♟️ Public Matchmaking Architecture

This document details the architecture for a public matchmaking queue, allowing users to be automatically paired with opponents of similar skill.

---

## Public Matchmaking Queue

*   **Concept:** Instead of a user choosing a specific opponent, they can enter a public queue for a specific game. A background process then pairs them with another waiting player, creating a match automatically. This is essential for quick, competitive play.

*   **Recommended Technology:** **Redis**. While a database table is feasible, Redis is purpose-built for this kind of fast, in-memory list management and is significantly more performant. We can use a Redis Sorted Set for each game.

*   **Implementation (Using Redis Sorted Sets):**
    1.  **Data Structure:** For each game, create a sorted set (e.g., `matchmaking:validate-four`).
        *   **Member:** The `user_id`.
        *   **Score:** The user's skill rating (Elo score or a simpler metric). This allows for skill-based matchmaking. If no skill rating exists, the score can be the timestamp of when they joined the queue.
    2.  **New API Endpoints:**
        *   `POST /v1/matchmaking/queue`: Adds the authenticated user to the queue for a specific game.
            *   **Body:** `{ "game_slug": "validate-four" }`
            *   **Logic:** Adds the `user_id` and their current skill rating to the corresponding Redis sorted set.
        *   `DELETE /v1/matchmaking/queue`: Removes the user from the queue if they cancel.
            *   **Body:** `{ "game_slug": "validate-four" }`
            *   **Logic:** Removes the `user_id` from the Redis sorted set.
    3.  **The Matchmaking Job (The Core Logic):**
        *   **Type:** A Laravel scheduled job (`php artisan make:job ProcessMatchmakingQueue`) that runs frequently (e.g., every 5-10 seconds).
        *   **Process:**
            a. The job iterates through each game's matchmaking queue (e.g., `matchmaking:validate-four`).
            b. It pulls two players from the set (`ZPOPMIN` or similar command).
            c. It could optionally look for players within a certain skill rating range to ensure fair matches.
            d. Once a pair is found, the job removes them from the queue.
            e. It then calls the existing `MatchService` (or a similar internal service) to create a new `Match` record for these two players.
            f. Finally, it broadcasts an event via **Laravel Reverb** to both users (e.g., on their private `user.{id}` channel) with the `ulid` of the new match, prompting their frontends to navigate to the game screen.

*   **Benefits of this Approach:**
    *   **Fast & Scalable:** Redis is extremely fast for these operations.
    *   **Decoupled:** The matchmaking logic is contained within a background job, so it doesn't block web requests.
    *   **Resilient:** If a user closes their app while in the queue, they simply remain in the Redis set until they are matched or a timeout job cleans them up.
