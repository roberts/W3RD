### Project: Gamer Protocol API

#### 1. Overview

The Gamer Protocol API is a centralized, multi-tenant game server built with Laravel 12. It is designed to be the single source of truth for tens to hundreds of frontend applications ("Interfaces"), which can range from web apps (React/TypeScript) to mobile & desktop apps, terminal interfaces, Telegram mini apps and bots. The API will handle all user authentication, game logic, matchmaking, real-time gameplay, and billing, allowing frontends to act as lightweight "skins" or themes. The system will feature both human players and AI-driven "Agents" that are treated as ordinary users to create a seamless experience.

#### 2. Core Technologies

*   **Framework:** Laravel 12
*   **Testing:** Pest v4
*   **Database:** MySQL 8.0+
*   **Real-Time Communication:** Laravel Reverb (WebSockets)
*   **Authentication:** Laravel Sanctum (API Tokens)
*   **Billing:** Laravel Cashier (Stripe Integration) or Mobile App Store & Telegram Mini App subscriptions.
*   **In-Memory Store:** Redis (for Matchmaking, Caching, and Job Queues)
*   **Admin Panel:** Filament v4

#### 3. Architectural Principles

Development will adhere strictly to the `constitution.md` document, which mandates:
*   **Code Quality:** A Service-Oriented Architecture with thin controllers and DRY principles.
*   **Testing Standards:** Comprehensive Feature and Unit tests for all endpoints and business logic.
*   **API Consistency:** Predictable JSON responses, use of ULIDs for public IDs, and mandatory dual-factor authorization.
*   **Performance:** Optimized queries, intelligent use of Redis, and extensive background job queuing.

#### 4. Database & Model Architecture

The architecture unifies all players, human and AI, under a central `User` model.

*   **`users` Table:** The primary identity table. It includes a `username` for unique identification and a nullable, unique `agent_id` foreign key. If `agent_id` is not null, the user is an AI.
*   **`agents` Table:** A "profile" table that extends `users`. It stores AI-specific data, such as `ai_logic_path` (the class path to its strategy) and `available_hour_est`.
*   **`players` Table:** A simple pivot table connecting a `Match` to a `User` via a standard `user_id` foreign key, eliminating polymorphic complexity.
*   **`matches` Table:** The core table for game instances, using a `ulid` for public reference and a flexible `game_state` JSON column to support any game type without schema changes. It will also include a `last_move_at` timestamp to handle turn timeouts.
*   **`games` Table:** A blueprint table defining available games and their rules, including a `move_timeout_seconds` column for game-specific turn timers.

#### 5. Agent & AI Architecture

Agents are designed to be indistinguishable from human players from the frontend's perspective.

*   **Identity:** An Agent is a standard `User` record linked to an `Agent` profile. This `User` record has a programmatically generated unique email (e.g., `bot_checkers@agents.gamerprotocol.io`) and a securely hashed random password.
*   **Scheduling:** A `SchedulingService` finds available agents based on their `available_hour_est` and their current "busy" status (i.e., not active in another match).
*   **AI Logic:** The `getAIMove` method within each game's service class will:
    1.  Access the agent's `User` model.
    2.  Retrieve the attached `Agent` profile.
    3.  Use the `ai_logic_path` to instantiate the correct internal strategy class (e.g., `ValidateFourMinimax`, `CheckersHeuristic`).
    4.  Support an `ExternalAgentAdapter` for agents whose logic is provided by a third-party API.

#### 6. Matchmaking & Gameplay

The system will feature an intelligent, Redis-based public matchmaking queue.

*   **Skill-Based Queue:** Players are added to a Redis sorted set, ordered by their game-specific **Level**.
*   **Rematch Prevention:** A Redis list will track each user's last 3 opponents to prevent immediate rematches.
*   **30-Second Agent Fallback:** If a human player waits in the queue for more than 30 seconds, the system will stop searching for human opponents and attempt to match them with an available, non-recent Agent.
*   **"Accept Match" Flow:** To prevent AFK players, a `MatchFound` event is dispatched via Reverb. Both clients must confirm within 10 seconds before the match is created. Failure to accept results in removal from the queue.
*   **Queue Dodge Penalty:** Users who repeatedly decline or fail to accept matches will receive a temporary matchmaking cooldown, managed via a Redis key with a TTL.
*   **Turn Timeout:** A scheduled job will run every minute to check for active matches that have exceeded their `move_timeout_seconds` (based on the `last_move_at` timestamp). Timed-out matches will be forfeited, and the result will be broadcast to the clients.

#### 7. Administration

A comprehensive admin panel will be built using **Filament v4** to manage all aspects of the platform, including user and agent management, match history review, billing and subscription management, and platform-wide analytics.
