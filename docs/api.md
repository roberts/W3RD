That's the final comprehensive document needed to lock down your **GamerProtocol.io** API development. A complete list of RESTful endpoints ensures your backend logic and frontend calls are fully aligned across all platforms (Web, Mobile, Telegram, CLI).

This list incorporates all requirements: **RESTful design, two-factor authorization, ULIDs for public IDs, and external billing integration.**

---

## 🔗 Comprehensive GamerProtocol.io API Endpoints

All endpoints use the base path `/v1/`.

* **Required Headers for Authorization:**
    * `Authorization: Bearer [Sanctum User Token]`
    * `X-Client-Key: [API Key from clients table]` (For application authorization)

### 1. 🔑 Authentication & User Management

| Resource | HTTP Method | Endpoint | Purpose | Auth Requirements |
| :--- | :--- | :--- | :--- | :--- |
| **Register** | `POST` | `/v1/auth/register` | **Standard Registration.** Creates a pending registration and sends a verification email. | `X-Client-Key` Only |
| **Verify** | `POST` | `/v1/auth/verify` | **Verify Email.** Verifies the token, creates the user, and returns a login token. | `X-Client-Key` Only |
| **Login** | `POST` | `/v1/auth/login` | **Standard Login.** Authenticates with email/password and returns a login token. | `X-Client-Key` Only |
| **Social Login** | `POST` | `/v1/auth/social` | **Social Login.** Authenticates with a provider's access token and returns a login token. | `X-Client-Key` Only |
| **Logout** | `POST` | `/v1/auth/logout` | **User Logout.** Revokes the current API token. | Bearer + Client Key |
| **User** | `GET` | `/v1/auth/user` | **Get User.** Retrieve the currently authenticated user's profile. | Bearer + Client Key |
| **User** | `PATCH` | `/v1/auth/user` | **Update User.** Update the authenticated user's profile data. | Bearer + Client Key |

---

### 2. ♟️ Matchmaking & Gameplay

These endpoints handle matchmaking, game management, and gameplay actions.

| Resource | HTTP Method | Endpoint | Purpose | Authentication |
| :--- | :--- | :--- | :--- | :--- |
| **Titles** | `GET` | `/v1/titles` | List all available **Game Titles** (returns GameTitle enum values and labels). | Public |
| **Title Rules** | `GET` | `/v1/titles/{gameTitle}/rules` | Get the rules for a specific game title. | Public |
| **Leaderboard** | `GET` | `/v1/leaderboard/{gameTitle}` | Get leaderboard for a specific game title. | Public |
| **Quickplay Join** | `POST` | `/v1/games/quickplay` | Join quickplay matchmaking queue. | Bearer + Client Key |
| **Quickplay Leave** | `DELETE` | `/v1/games/quickplay` | Leave quickplay matchmaking queue. | Bearer + Client Key |
| **Quickplay Accept** | `POST` | `/v1/games/quickplay/accept` | Accept a quickplay match. | Bearer + Client Key |
| **Lobbies** | `GET` | `/v1/games/lobbies` | List available lobbies. | Bearer + Client Key |
| **Lobbies** | `POST` | `/v1/games/lobbies` | Create a new lobby. | Bearer + Client Key |
| **Lobby** | `GET` | `/v1/games/lobbies/{lobby_ulid}` | Get lobby details. | Bearer + Client Key |
| **Lobby** | `DELETE` | `/v1/games/lobbies/{lobby_ulid}` | Delete/leave a lobby. | Bearer + Client Key |
| **Lobby Ready Check** | `POST` | `/v1/games/lobbies/{lobby_ulid}/ready-check` | Initiate ready check for lobby. | Bearer + Client Key |
| **Lobby Players** | `POST` | `/v1/games/lobbies/{lobby_ulid}/players` | Add player to lobby. | Bearer + Client Key |
| **Lobby Players** | `PUT` | `/v1/games/lobbies/{lobby_ulid}/players/{user_id}` | Update player status in lobby. | Bearer + Client Key |
| **Lobby Players** | `DELETE` | `/v1/games/lobbies/{lobby_ulid}/players/{user_id}` | Remove player from lobby. | Bearer + Client Key |
| **Games** | `GET` | `/v1/games` | List the authenticated user's active and recent finished games. | Bearer + Client Key |
| **Game** | `GET` | `/v1/games/{gameUlid}` | Retrieve the current **Game state** by its public **ULID**. | Bearer + Client Key |
| **Game History** | `GET` | `/v1/games/{gameUlid}/history` | Retrieve the full **Action** history for the game (for replay). | Bearer + Client Key |
| **Game Rematch** | `POST` | `/v1/games/{gameUlid}/rematch` | Request a rematch with the same opponent. | Bearer + Client Key |
| **Game Action** | `POST` | `/v1/games/{gameUlid}/action` | **EXECUTE** an action. Body contains `action_type` and `action_details` (JSON). | Bearer + Client Key |
| **Available Actions** | `GET` | `/v1/games/{gameUlid}/available-actions` | Get list of valid actions for current game state. | Bearer + Client Key |
| **Accept Rematch** | `POST` | `/v1/games/rematch/{requestId}/accept` | Accept a rematch request. | Bearer + Client Key |
| **Decline Rematch** | `POST` | `/v1/games/rematch/{requestId}/decline` | Decline a rematch request. | Bearer + Client Key |

---

### 3. 💰 Billing & Subscriptions

These endpoints manage plan status, quotas, and handle external payment confirmation flows.

| Resource | HTTP Method | Endpoint | Purpose | Authentication |
| :--- | :--- | :--- | :--- | :--- |
| **Plans** | `GET` | `/v1/billing/plans` | Get available subscription plans. | Bearer + Client Key |
| **Billing Status** | `GET` | `/v1/billing/status` | Retrieve user's current plan level (Member, Master) and renewal details. | Bearer + Client Key |
| **Subscribe** | `POST` | `/v1/billing/subscribe` | Initiate a new subscription or plan change (returns a Cashier/Stripe checkout URL). | Bearer + Client Key |
| **Manage Subscription** | `GET` | `/v1/billing/manage` | Get Stripe customer portal URL for managing subscription. | Bearer + Client Key |
| **Verify Receipt** | `POST` | `/v1/billing/{provider}/verify` | **Receipt Verification.** Receives purchase token from iOS/Android app to verify with Apple/Google and update local subscription. Provider is 'apple' or 'google'. | Bearer + Client Key |
| **Stripe Webhook** | `POST` | `/v1/stripe/webhook` | General **Stripe Webhook** for core Laravel Cashier events. | None (Vendor Auth) |

---

## 🔗 Comprehensive API Endpoint Recap

Here is the comprehensive recap of all **GamerProtocol.io** API endpoints, detailing the HTTP method, required parameters, and expected response data.

### I. Identity & Core Access

All requests require `Authorization: Bearer [User Token]` and `X-Client-Key`, unless noted.

| Endpoint | HTTP Method | Request Body Data (Input) | Response Body Data (Output) |
| :--- | :--- | :--- | :--- |
| `/v1/auth/user` | `GET` | *(None)* | `user` (Full profile, including `avatar_id`, `email`, `deactivated_at`) |
| `/v1/auth/user` | `PATCH` | `name` (Optional), `password` (Optional) | `user` (Updated profile object) |

---

### II. Matchmaking & Gameplay

Gameplay utilizes the **ULID** for security and relies heavily on **JSON** payloads.

| Endpoint | HTTP Method | Request Body Data (Input) | Response Body Data (Output) |
| :--- | :--- | :--- | :--- |
| `/v1/titles` | `GET` | *(None)* | Array of game title objects with GameTitle enum values (`value`, `label`, `max_players`) |
| `/v1/games` | `POST` | `game_title` (GameTitle enum value, e.g., 'validate-four'), `opponent_type` ('agent' or 'user'), `opponent_id` (ID of specific agent/user) | `game` (New game object including **`ulid`** and initial **`game_state`** JSON) |
| `/v1/games` | `GET` | *(Query params: `status`, `limit`)* | Array of recent/active `game` objects |
| `/v1/games/{ulid}` | `GET` | *(None)* | `game` (Current game object, including **`game_state` JSON** and **`players`** array) |
| `/v1/games/{ulid}/actions` | `POST` | **`action_type`** (ActionType enum value: 'drop_piece', 'move_piece', 'play_card', 'pass', 'draw_card', 'bid'), **`action_details`** (JSON, e.g., `{"column": 3}` or `{"card_id": 42}`) | `action` (The recorded action object with status and timestamps) |
| `/v1/games/{ulid}/actions` | `GET` | *(None)* | Array of all `action` objects for the game history |

---

### III. Billing, Quotas & Gamification

| Endpoint | HTTP Method | Request Body Data (Input) | Response Body Data (Output) |
| :--- | :--- | :--- | :--- |
| `/v1/billing/plans` | `GET` | *(None)* | Array of available subscription plan objects |
| `/v1/billing/status` | `GET` | *(None)* | `subscription` (Plan level, renewal date, status) |
| `/v1/billing/subscribe` | `POST` | `plan_id` ('member' or 'master'), `payment_method_token` (from client-side Stripe) | `checkout_url` or `status: success` |
| `/v1/billing/manage` | `GET` | *(None)* | `url` (Stripe customer portal URL) |
| `/v1/billing/{provider}/verify` | `POST` | `receipt_data` (App Store or Google Play token), provider in path ('apple' or 'google') | `status: success`, `subscription` (Updated local record) |
| `/v1/me/stats` | `GET` | *(None)* | `stats` (Object: **`total_points`**, **`global_rank`**, **`wins`**, **`losses`**) |
| `/v1/me/levels` | `GET` | *(None)* | Array of **`UserTitleLevel`** objects (GameTitle enum values, `level`, `xp_current`) |
| `/v1/user/badges` | `GET` | *(None)* | Array of earned `badge` objects (including `earned_at` timestamp) |
| `/v1/leaderboard` | `GET` | *(Query params: `limit`, `offset`)* | Array of `user` objects ranked by **`total_points`** |
| `/v1/leaderboard/{game_title}` | `GET` | *(Query params: `limit`)* | Array of `user` objects ranked by **Game Title-Specific Metric** (e.g., win percentage) where game_title is a GameTitle enum value |
| `/v1/leaderboard/daily/history/{date}` | `GET` | *(None)* | Array of `user` objects ranked by points earned on that specific date |

Based on your current architecture, which includes **Sanctum Authentication**, **Laravel Cashier/Reverb**, **Polymorphic Agents**, and comprehensive **Gamification**, you have covered all core functionalities.

However, a robust, production-ready application typically requires a few utility and maintenance endpoints that enhance the user experience, security, and data integrity.

Here are the essential missing utility and maintenance API endpoints you should add:

---

## 1. 🛡️ Security & Integrity Endpoints

These endpoints allow users to manage game state and ensure system integrity.

| Resource | HTTP Method | Endpoint | Purpose | Request Body Data | Response Body Data |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Forfeit** | `POST` | `/v1/games/{ulid}/forfeit` | Allows the current player to **forfeit/concede** a game. This is faster than waiting for a forced timeout. | *(None)* | `game` (Updated status: `finished`) |

---

## 2. ⚙️ Utilities & Data Endpoints

These endpoints improve the user experience and help synchronize data across various frontends (Web, Mobile).

| Resource | HTTP Method | Endpoint | Purpose | Request Body Data | Response Body Data |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Config** | `GET` | `/v1/config` | Retrieves general platform configuration data (e.g., game rules, max difficulty, maintenance mode status). | *(None)* | `config` (JSON object of non-sensitive config values) |
| **Alerts** | `GET` | `/v1/me/alerts` | Retrieves user-specific alerts (e.g., "Game X has started," "Badge Y earned"). You would likely use Reverb for real-time, but need an endpoint for history. | *(None)* | Paginated array of `alert` objects |
| **Mark Alerts Read** | `POST` | `/v1/me/alerts/mark-as-read` | Marks specific alerts or all alerts as read. | `alert_ids` (Optional array of IDs, if omitted marks all as read) | `status: success` |
| **System Time** | `GET` | `/v1/time` | Returns the current server time and the **EST Timezone** status. **CRITICAL** for clients to calculate quotas/strikes against the midnight EST cutoff. | *(None)* | `timestamp` (UTC), `timezone` (EST), `hour_est` |

---

## 3. 💰 Billing Management Endpoints

These are necessary for users to manage their payment methods directly (required by Cashier).

| Resource | HTTP Method | Endpoint | Purpose | Request Body Data | Response Body Data |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Payment Method** | `GET` | `/v1/billing/payment-methods` | Lists saved payment methods (last four digits, card type). | *(None)* | Array of payment method objects |
| **Payment Method** | `POST` | `/v1/billing/payment-methods` | Adds a new payment method via a **Stripe Token** or similar payment ID. | `token` (Stripe token) | `status: success` |
| **Subscription** | `DELETE` | `/v1/billing/subscription` | Cancels the user's current recurring subscription (Cashier). | *(None)* | `status: success` |