# Quickstart: Lobby and Matchmaking

This document provides a high-level overview of the key components and flows for developers to get started with the new lobby and matchmaking system.

## 1. Public Matchmaking Flow

The public matchmaking system is orchestrated by the `ProcessMatchmakingQueue` job and relies heavily on Redis.

1.  **Joining the Queue**: A user sends a `POST` request to `/api/v1/games/quickplay`. The `MatchmakingController` adds the user's ID to the Redis sorted set for the specified game title and mode.
2.  **Processing the Queue**: The `ProcessMatchmakingQueue` job runs every 5-10 seconds.
    *   It fetches players from the queue.
    *   It checks their wait time from the `matchmaking:timestamps` Redis hash.
    *   **AI Fallback (>30s)**: Calls `SchedulingService->findAvailableAgent()`.
    *   **Human Match (<30s)**: Finds a suitable opponent, avoiding recent players stored in the `recent_opponents:{user_id}` Redis list.
3.  **Confirmation**:
    *   On finding a match, the job dispatches a `GameFound` event.
    *   It creates a temporary `matchmaking:confirm:{match_id}` hash in Redis with a 15s TTL.
    *   Users send a `POST` to `/api/v1/games/quickplay/accept`.
    *   Once both users accept, the `Game` is created, and a `GameStarted` event is broadcast.
4.  **Dodge Penalty**: Failing to accept results in a `cooldown:matchmaking:{user_id}` key being set in Redis, temporarily blocking re-entry to the queue.

## 2. Lobby System Flow

The lobby system is a persistent, database-driven feature for creating custom games.

1.  **Lobby Creation**: A host sends a `POST` request to `/api/v1/games/lobbies`. The `LobbyController` creates a `Lobby` record and associated `LobbyPlayer` records.
    *   For private lobbies, `LobbyInvitation` events are dispatched to invitees.
    *   Public lobbies will be visible via a `GET` request to `/api/v1/games/lobbies`.
2.  **Responding to Invites**: Invitees use `PUT /api/v1/games/lobbies/{lobby_ulid}/players/{user_id}` to accept or decline.
3.  **Game Start (Immediate)**: When the `min_players` count is met by accepted players, the last player to accept triggers the game creation process.
4.  **Game Start (Scheduled)**: The `ProcessScheduledLobbies` job runs every minute.
    *   It finds lobbies where `scheduled_at` is now.
    *   If `min_players` is met, it creates the `Game`.
    *   If not, it cancels the lobby.
5.  **Game Handoff**: In all cases, starting the game involves:
    *   Creating the `Game` and `GamePlayer` records in the database.
    *   Updating the `Lobby` status to `completed`.
    *   Broadcasting a `GameStarted` event to all players.

## 3. Key Components

*   **Controllers**: `QuickplayController`, `LobbyController`, `LobbyPlayerController`.
*   **Jobs**: `ProcessMatchmakingQueue`, `ProcessScheduledLobbies`.
*   **Models**: `Lobby`, `LobbyPlayer`.
*   **Events**: `GameFound`, `LobbyInvitation`, `GameStarted`, `LobbyReadyCheck`.
*   **Redis Keys**: `matchmaking:*`, `recent_opponents:*`, `cooldown:*`.
