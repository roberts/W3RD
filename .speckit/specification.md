### Project: Gamer Protocol API

#### 1. Overview

The Gamer Protocol API is a centralized, multi-tenant game server built with Laravel 12. It is designed to be the single source of truth for tens to hundreds of frontend applications ("Interfaces"), which can range from web apps (React/TypeScript) to mobile & desktop apps, terminal interfaces, and Telegram mini-apps. The API will handle all user authentication, game logic, matchmaking, real-time gameplay, and billing, allowing frontends to act as lightweight "skins." The system will feature both human players and AI-driven "Agents" that are treated as ordinary users to create a seamless experience.

#### 2. Core Technologies

*   **Framework:** Laravel 12
*   **Testing:** Pest v4
*   **Database:** MySQL 8.0+
*   **Real-Time Communication:** Laravel Reverb (WebSockets)
*   **Authentication:** Laravel Sanctum (API Tokens)
*   **Billing:** Laravel Cashier (Stripe Integration), with support for Mobile App Store & Telegram Mini App subscriptions.
*   **In-Memory Store:** Redis (for Matchmaking, Caching, and Job Queues)
*   **Admin Panel:** Filament v4

#### 3. Architectural Principles

Development will adhere strictly to the `constitution.md` document, which mandates:
*   **Code Quality:** A Service-Oriented Architecture with thin controllers and DRY principles.
*   **Testing Standards:** Comprehensive Feature and Unit tests for all endpoints and business logic.
*   **API Consistency:** Predictable JSON responses, use of ULIDs for public IDs, and mandatory dual-factor authorization (`Bearer Token` + `X-Interface-Key`).
*   **Performance:** Optimized queries, intelligent use of Redis, and extensive background job queuing.

#### 4. Database & Model Architecture

The architecture unifies all players, human and AI, under a central `User` model.

*   **`users` Table:** The primary identity table. It includes a `username` for unique identification and a nullable, unique `agent_id` foreign key. If `agent_id` is not null, the user is an AI.
*   **`agents` Table:** A "profile" table that extends `users`. It stores AI-specific data, such as `ai_logic_path` (the class path to its strategy) and `available_hour_est`.
*   **`titles` Table:** Defines available game titles (like validate-four, checkers, hearts) with their slug, name, and max_players.
*   **`players` Table:** A simple pivot table connecting a `Game` to a `User` via a standard `user_id` foreign key, eliminating polymorphic complexity.
*   **`games` Table:** The core table for individual game instances, using a `ulid` for public reference and a flexible `game_state` JSON column. It includes `title_slug` (storing GameTitle enum values) to reference which game title is being played and a `last_move_at` timestamp to handle turn timeouts.
*   **`entries` Table:** Tracks each user entry (login session) when accessing the platform through any client, including login/logout timestamps and device info.
*   **Gamification Tables:** A suite of tables including `point_ledgers`, `global_ranks`, `badges`, `user_badge`, and `user_title_levels` will manage all aspects of the gamification system.

#### 5. Agent & AI Architecture

Agents are designed to be indistinguishable from human players from the frontend's perspective.

*   **Identity:** An Agent is a standard `User` record linked to an `Agent` profile.
*   **Scheduling:** A `SchedulingService` finds available agents based on their `available_hour_est` and their current "busy" status.
*   **AI Logic:** The `getAIMove` method within each game title's service class will use the `ai_logic_path` on the agent's profile to instantiate the correct internal strategy class (e.g., `ValidateFourMinimax`, `CheckersHeuristic`).

#### 6. Matchmaking & Gameplay

The system will feature an intelligent, Redis-based public matchmaking queue.

*   **Skill-Based Queue:** Players are added to a Redis sorted set, ordered by their game title-specific **Level**.
*   **Rematch Prevention:** A Redis list will track each user's last 3 opponents to prevent immediate rematches.
*   **30-Second Agent Fallback:** If a human player waits in the queue for more than 30 seconds, the system will attempt to match them with an available, non-recent Agent.
*   **"Accept Game" Flow:** To prevent AFK players, a `GameFound` event is dispatched via Reverb. Both clients must confirm within 10 seconds before the game is created.
*   **Queue Dodge Penalty:** Users who repeatedly decline or fail to accept games will receive a temporary matchmaking cooldown, managed via a Redis key with a TTL.
*   **Turn Timeout:** A scheduled job will run every minute to check for active games that have exceeded their `move_timeout_seconds`. Timed-out games will be forfeited.

#### 7. Gamification System

A multi-layered gamification system will drive engagement and retention.

*   **Points & Ranks:** Users earn points from games, which determine a `GlobalRank`.
*   **Levels & XP:** Users gain game title-specific XP to increase their `Level` in each game title, which is used for matchmaking.
*   **Badges:** Permanent, one-time achievements for reaching specific milestones.
*   **Level Decay:** A scheduled task will reduce a user's `Level` in a specific game title if they are inactive for a set period, encouraging continued play.

#### 8. API Endpoints

The API will expose a comprehensive set of RESTful endpoints, including:
*   **Identity:** `/v1/entries`, `/v1/user`
*   **Gameplay:** `/v1/titles`, `/v1/games`, `/v1/games/{ulid}/moves`
*   **Matchmaking:** `/v1/matchmaking/queue`, `/v1/matchmaking/accept`
*   **Billing:** `/v1/billing/subscription`, `/v1/billing/mobile/verify`
*   **Gamification:** `/v1/user/stats`, `/v1/user/levels`, `/v1/leaderboard`

#### 9. Administration

A comprehensive admin panel will be built using **Filament v4** to manage users, agents, game titles, games, billing, content (avatars), and platform-wide analytics.

#### 10. Future Expansion

The architecture is designed to accommodate future features outlined in `expansion.md`, including:
*   **Social Features:** Friends lists and real-time game chat.
*   **Gameplay Variety:** Tournaments and asynchronous (turn-based) gameplay for compatible game titles.
*   **Monetization:** An in-game store for cosmetics and a ticket-based (pay-per-game) system.
*   **Team Play:** Party/group queuing for team-based game titles.
