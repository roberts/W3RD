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

These endpoints handle the creation of games and the execution of actions, relying on your **Game Service Handlers**.

| Resource | HTTP Method | Endpoint | Purpose | Authentication |
| :--- | :--- | :--- | :--- | :--- |
| **Titles** | `GET` | `/v1/titles` | List all available **Game Titles** (returns GameTitle enum values and labels). | Bearer + Client Key |
| **Games** | `POST` | `/v1/games` | **CREATE** a new game. Triggers the **Strike/Quota check**. Body specifies `game_title` (GameTitle enum value) and initial `players`. | Bearer + Client Key |
| **Games** | `GET` | `/v1/games` | List the authenticated user's active and recent finished games. | Bearer + Client Key |
| **Games** | `GET` | `/v1/games/{ulid}` | Retrieve the current **Game state** (`game_state` JSON) by its public **ULID**. | Bearer + Client Key |
| **Actions** | `POST` | `/v1/games/{ulid}/actions` | **EXECUTE** an action. Body contains `action_type` (ActionType enum value) and `action_details` (JSON). Triggers validation, state update, and **Reverb broadcast**. | Bearer + Client Key |
| **Actions** | `GET` | `/v1/games/{ulid}/actions` | Retrieve the full **Action** history for the game (for replay). | Bearer + Client Key |

---

### 3. 💰 Billing & Subscriptions

These endpoints manage plan status, quotas, and handle external payment confirmation flows.

| Resource | HTTP Method | Endpoint | Purpose | Authentication |
| :--- | :--- | :--- | :--- | :--- |
| **Subscription** | `GET` | `/v1/billing/subscription` | Retrieve user's current plan level (Member, Master) and renewal details. | Bearer + Client Key |
| **Quotas** | `GET` | `/v1/billing/quotas` | Retrieve user's current limits for **Strikes** (daily losses) and **Quotas** (monthly matches). | Bearer + Client Key |
| **Web/Stripe** | `POST` | `/v1/billing/subscribe` | Initiate a new subscription or plan change (returns a Cashier/Stripe checkout URL). | Bearer + Client Key |
| **Mobile** | `POST` | `/v1/billing/mobile/verify` | **Receipt Verification.** Receives purchase token from iOS/Android app to verify with Apple/Google and update local subscription. | Bearer + Client Key |
| **Mobile** | `POST` | `/v1/billing/mobile/webhook` | Receives server-to-server **renewal/cancellation** webhooks from Apple/Google. | None (Vendor Auth) |
| **Telegram** | `POST` | `/v1/billing/telegram/webhook` | Receives **payment confirmation** from the payment provider used within the Telegram interface. | None (Vendor Auth) |
| **Stripe** | `POST` | `/v1/stripe/webhook` | General **Stripe Webhook** for core Laravel Cashier events. | None (Vendor Auth) |


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
| `/v1/billing/subscription` | `GET` | *(None)* | `subscription` (Plan level, renewal date, status) |
| `/v1/billing/quotas` | `GET` | *(None)* | `quotas` (Array of objects with GameTitle enum values, `games_started`, `strikes_used`) |
| `/v1/billing/subscribe` | `POST` | `plan_id` ('member' or 'master'), `payment_method_token` (from client-side Stripe) | `checkout_url` or `status: success` |
| `/v1/billing/mobile/verify` | `POST` | `receipt_data` (App Store or Google Play token), `provider` ('apple' or 'google') | `status: success`, `subscription` (Updated local record) |
| `/v1/user/stats` | `GET` | *(None)* | `stats` (Object: **`total_points`**, **`global_rank`**, **`wins`**, **`losses`**) |
| `/v1/user/levels` | `GET` | *(None)* | Array of **`UserTitleLevel`** objects (GameTitle enum values, `level`, `xp_current`) |
| `/v1/user/badges` | `GET` | *(None)* | Array of earned `badge` objects (including `earned_at` timestamp) |
| `/v1/leaderboard` | `GET` | *(Query params: `limit`, `offset`)* | Array of `user` objects ranked by **`total_points`** |
| `/v1/titles/{game_title}/leaderboard` | `GET` | *(Query params: `limit`)* | Array of `user` objects ranked by **Game Title-Specific Metric** (e.g., win percentage) where game_title is a GameTitle enum value |
| `/v1/leaderboard/daily/history/{date}` | `GET` | *(None)* | Array of `user` objects ranked by points earned on that specific date |

Based on your current architecture, which includes **Sanctum Authentication**, **Laravel Cashier/Reverb**, **Polymorphic Agents**, and comprehensive **Gamification**, you have covered all core functionalities.

However, a robust, production-ready application typically requires a few utility and maintenance endpoints that enhance the user experience, security, and data integrity.

Here are the essential missing utility and maintenance API endpoints you should add:

---

## 1. 🛡️ Security & Integrity Endpoints

These endpoints allow users to manage their devices and ensure the API's public data is up-to-date.

| Resource | HTTP Method | Endpoint | Purpose | Request Body Data | Response Body Data |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **User Tokens** | `GET` | `/v1/user/tokens` | Lists all active API tokens (entries) for the current user. | *(None)* | Array of active token objects (`id`, `name`, `last_used_at`) |
| **User Tokens** | `DELETE` | `/v1/user/tokens/{id}` | Revokes a specific active token/entry (e.g., "log out of all other devices"). | *(None)* | `status: success` |
| **Cleanup** | `POST` | `/v1/games/{ulid}/forfeit` | Allows the current player to **forfeit/concede** a game. This is faster than waiting for a forced timeout. | *(None)* | `game` (Updated status: `finished`) |
| **Game** | `DELETE` | `/v1/games/{ulid}` | **Soft Deletes** an active game. Useful for clearing failed/stuck matchmaking attempts. *Requires specific soft delete logic in your controller.* | *(None)* | `status: success` |

---

## 2. ⚙️ Utilities & Data Endpoints

These endpoints improve the user experience and help synchronize data across various frontends (Web, Mobile).

| Resource | HTTP Method | Endpoint | Purpose | Request Body Data | Response Body Data |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Config** | `GET` | `/v1/config` | Retrieves general platform configuration data (e.g., game rules, max difficulty, maintenance mode status). | *(None)* | `config` (JSON object of non-sensitive config values) |
| **Notification** | `GET` | `/v1/notifications` | Retrieves user-specific notifications (e.g., "Game X has started," "Badge Y earned"). You would likely use Reverb for real-time, but need an endpoint for history. | *(None)* | Array of `notification` objects |
| **Notification** | `PATCH` | `/v1/notifications/{id}` | Marks a specific notification as read. | *(None)* | `status: success` |
| **System Time** | `GET` | `/v1/time` | Returns the current server time and the **EST Timezone** status. **CRITICAL** for clients to calculate quotas/strikes against the midnight EST cutoff. | *(None)* | `timestamp` (UTC), `timezone` (EST), `hour_est` |

---

## 3. 💰 Billing Management Endpoints

These are necessary for users to manage their payment methods directly (required by Cashier).

| Resource | HTTP Method | Endpoint | Purpose | Request Body Data | Response Body Data |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Payment Method** | `GET` | `/v1/billing/payment-methods` | Lists saved payment methods (last four digits, card type). | *(None)* | Array of payment method objects |
| **Payment Method** | `POST` | `/v1/billing/payment-methods` | Adds a new payment method via a **Stripe Token** or similar payment ID. | `token` (Stripe token) | `status: success` |
| **Subscription** | `DELETE` | `/v1/billing/subscription` | Cancels the user's current recurring subscription (Cashier). | *(None)* | `status: success` |