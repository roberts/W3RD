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
