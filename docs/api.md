That's the final comprehensive document needed to lock down your **GamerProtocol.io** API development. A complete list of RESTful endpoints ensures your backend logic and frontend calls are fully aligned across all platforms (Web, Mobile, Telegram, CLI).

This list incorporates all requirements: **RESTful design, two-factor authorization, ULIDs for public IDs, and external billing integration.**

---

## 🔗 Comprehensive GamerProtocol.io API Endpoints

All endpoints use the base path `/v1/`.

* **Required Headers for Authorization:**
    * `Authorization: Bearer [Sanctum User Token]`
    * `X-Interface-Key: [API Key from Interfaces table]` (For application authorization)

### 1. 🔑 Identity & Core Access

| Resource | HTTP Method | Endpoint | Purpose | Auth Requirements |
| :--- | :--- | :--- | :--- | :--- |
| **Sessions** | `POST` | `/v1/sessions` | **User Login.** Authenticates credentials and returns a Sanctum API token. | `X-Interface-Key` Only |
| **Sessions** | `DELETE` | `/v1/sessions` | **User Logout.** Revokes the current API token. | Bearer + Interface Key |
| **User** | `GET` | `/v1/user` | Retrieve the currently authenticated user's profile and plan status. | Bearer + Interface Key |
| **User** | `PATCH` | `/v1/user` | Update user profile data (name, password, avatar choice). | Bearer + Interface Key |
| **Avatars** | `GET` | `/v1/avatars` | List all available **Avatar** assets (free, premium, NFT). | Bearer + Interface Key |
| **Agents** | `GET` | `/v1/agents` | List available AI/Local **Agent** profiles (for matchmaking setup). | Bearer + Interface Key |

---

### 2. ♟️ Matchmaking & Gameplay

These endpoints handle the creation of matches and the execution of moves, relying on your **Game Service Handlers**.

| Resource | HTTP Method | Endpoint | Purpose | Authentication |
| :--- | :--- | :--- | :--- | :--- |
| **Games** | `GET` | `/v1/games` | List all available **Game** blueprints (`validate-four`, `hearts`). | Bearer + Interface Key |
| **Matches** | `POST` | `/v1/matches` | **CREATE** a new match. Triggers the **Strike/Quota check**. Body specifies `game_slug` and initial `players`. | Bearer + Interface Key |
| **Matches** | `GET` | `/v1/matches` | List the authenticated user's active and recent finished matches. | Bearer + Interface Key |
| **Match** | `GET` | `/v1/matches/{ulid}` | Retrieve the current **Match state** (`game_state` JSON) by its public **ULID**. | Bearer + Interface Key |
| **Moves** | `POST` | `/v1/matches/{ulid}/moves` | **EXECUTE** a move. Body contains `move_details` (JSON). Triggers validation, state update, and **Reverb broadcast**. | Bearer + Interface Key |
| **Moves** | `GET` | `/v1/matches/{ulid}/moves` | Retrieve the full **Move** history for the match (for replay). | Bearer + Interface Key |

---

### 3. 💰 Billing & Subscriptions

These endpoints manage plan status, quotas, and handle external payment confirmation flows.

| Resource | HTTP Method | Endpoint | Purpose | Authentication |
| :--- | :--- | :--- | :--- | :--- |
| **Subscription** | `GET` | `/v1/billing/subscription` | Retrieve user's current plan level (Member, Master) and renewal details. | Bearer + Interface Key |
| **Quotas** | `GET` | `/v1/billing/quotas` | Retrieve user's current limits for **Strikes** (daily losses) and **Quotas** (monthly matches). | Bearer + Interface Key |
| **Web/Stripe** | `POST` | `/v1/billing/subscribe` | Initiate a new subscription or plan change (returns a Cashier/Stripe checkout URL). | Bearer + Interface Key |
| **Mobile** | `POST` | `/v1/billing/mobile/verify` | **Receipt Verification.** Receives purchase token from iOS/Android app to verify with Apple/Google and update local subscription. | Bearer + Interface Key |
| **Mobile** | `POST` | `/v1/billing/mobile/webhook` | Receives server-to-server **renewal/cancellation** webhooks from Apple/Google. | None (Vendor Auth) |
| **Telegram** | `POST` | `/v1/billing/telegram/webhook` | Receives **payment confirmation** from the payment provider used within the Telegram interface. | None (Vendor Auth) |
| **Stripe** | `POST` | `/v1/stripe/webhook` | General **Stripe Webhook** for core Laravel Cashier events. | None (Vendor Auth) |


---

## 🔗 Comprehensive API Endpoint Recap

Here is the comprehensive recap of all **GamerProtocol.io** API endpoints, detailing the HTTP method, required parameters, and expected response data.

### I. Identity & Core Access

All requests require `Authorization: Bearer [User Token]` and `X-Interface-Key`, unless noted.

| Endpoint | HTTP Method | Request Body Data (Input) | Response Body Data (Output) |
| :--- | :--- | :--- | :--- |
| `/v1/sessions` | `POST` | `email`, `password` | `token` (Bearer), `user` (User profile object) |
| `/v1/sessions` | `DELETE` | *(None)* | `status: success` (204 No Content) |
| `/v1/user` | `GET` | *(None)* | `user` (Full profile, including `avatar_id`, `email`, `deactivated_at`) |
| `/v1/user` | `PATCH` | `name` (Optional), `password` (Optional) | `user` (Updated profile object) |
| `/v1/avatars` | `GET` | *(None)* | Array of `avatar` objects (`id`, `name`, `image_url`, `type`) |
| `/v1/agents` | `GET` | *(None)* | Array of `agent` objects (`id`, `name`, `available_hour_est`, `avatar_id`) |

---

### II. Matchmaking & Gameplay

Gameplay utilizes the **ULID** for security and relies heavily on **JSON** payloads.

| Endpoint | HTTP Method | Request Body Data (Input) | Response Body Data (Output) |
| :--- | :--- | :--- | :--- |
| `/v1/games` | `GET` | *(None)* | Array of `game` objects (`slug`, `name`, `max_players`) |
| `/v1/matches` | `POST` | `game_slug` (e.g., 'validate-four'), `opponent_type` ('agent' or 'user'), `opponent_id` (ID of specific agent/user) | `match` (New match object including **`ulid`** and initial **`game_state`** JSON) |
| `/v1/matches` | `GET` | *(Query params: `status`, `limit`)* | Array of recent/active `match` objects |
| `/v1/matches/{ulid}` | `GET` | *(None)* | `match` (Current match object, including **`game_state` JSON** and **`players`** array) |
| `/v1/matches/{ulid}/moves` | `POST` | **`move_details`** (JSON, e.g., `{"column": 3}` or `{"card_id": 42}`) | `move` (The recorded move object) |
| `/v1/matches/{ulid}/moves` | `GET` | *(None)* | Array of all `move` objects for the match history |

---

### III. Billing, Quotas & Gamification

| Endpoint | HTTP Method | Request Body Data (Input) | Response Body Data (Output) |
| :--- | :--- | :--- | :--- |
| `/v1/billing/subscription` | `GET` | *(None)* | `subscription` (Plan level, renewal date, status) |
| `/v1/billing/quotas` | `GET` | *(None)* | `quotas` (Array of objects detailing `game_slug`, `matches_started`, `strikes_used`) |
| `/v1/billing/subscribe` | `POST` | `plan_id` ('member' or 'master'), `payment_method_token` (from client-side Stripe) | `checkout_url` or `status: success` |
| `/v1/billing/mobile/verify` | `POST` | `receipt_data` (App Store or Google Play token), `provider` ('apple' or 'google') | `status: success`, `subscription` (Updated local record) |
| `/v1/user/stats` | `GET` | *(None)* | `stats` (Object: **`total_points`**, **`global_rank`**, **`wins`**, **`losses`**) |
| `/v1/user/levels` | `GET` | *(None)* | Array of **`UserGameLevel`** objects (`game_slug`, `level`, `xp_current`) |
| `/v1/user/badges` | `GET` | *(None)* | Array of earned `badge` objects (including `earned_at` timestamp) |
| `/v1/leaderboard` | `GET` | *(Query params: `limit`, `offset`)* | Array of `user` objects ranked by **`total_points`** |
| `/v1/games/{slug}/leaderboard` | `GET` | *(Query params: `limit`)* | Array of `user` objects ranked by **Game-Specific Metric** (e.g., win percentage) |
| `/v1/leaderboard/daily/history/{date}` | `GET` | *(None)* | Array of `user` objects ranked by points earned on that specific date |

Based on your current architecture, which includes **Sanctum Authentication**, **Laravel Cashier/Reverb**, **Polymorphic Agents**, and comprehensive **Gamification**, you have covered all core functionalities.

However, a robust, production-ready application typically requires a few utility and maintenance endpoints that enhance the user experience, security, and data integrity.

Here are the essential missing utility and maintenance API endpoints you should add:

---

## 1. 🛡️ Security & Integrity Endpoints

These endpoints allow users to manage their devices and ensure the API's public data is up-to-date.

| Resource | HTTP Method | Endpoint | Purpose | Request Body Data | Response Body Data |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **User Tokens** | `GET` | `/v1/user/tokens` | Lists all active API tokens (sessions) for the current user. | *(None)* | Array of active token objects (`id`, `name`, `last_used_at`) |
| **User Tokens** | `DELETE` | `/v1/user/tokens/{id}` | Revokes a specific active token/session (e.g., "log out of all other devices"). | *(None)* | `status: success` |
| **Cleanup** | `POST` | `/v1/matches/{ulid}/forfeit` | Allows the current player to **forfeit/concede** a match. This is faster than waiting for a forced timeout. | *(None)* | `match` (Updated status: `finished`) |
| **Match** | `DELETE` | `/v1/matches/{ulid}` | **Soft Deletes** an active match. Useful for clearing failed/stuck matchmaking attempts. *Requires specific soft delete logic in your controller.* | *(None)* | `status: success` |

---

## 2. ⚙️ Utilities & Data Endpoints

These endpoints improve the user experience and help synchronize data across various frontends (Web, Mobile).

| Resource | HTTP Method | Endpoint | Purpose | Request Body Data | Response Body Data |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Config** | `GET` | `/v1/config` | Retrieves general platform configuration data (e.g., game rules, max difficulty, maintenance mode status). | *(None)* | `config` (JSON object of non-sensitive config values) |
| **Notification** | `GET` | `/v1/notifications` | Retrieves user-specific notifications (e.g., "Match X has started," "Badge Y earned"). You would likely use Reverb for real-time, but need an endpoint for history. | *(None)* | Array of `notification` objects |
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