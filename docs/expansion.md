# 🚀 GamerProtocol.io API Expansion Ideas

This document outlines potential new features to expand the GamerProtocol.io API beyond its core gameplay loop, focusing on community, monetization, and gameplay variety.

---

## 1. 🤝 Social & Community Features

### Friends System

*   **Concept:** Allow users to build a friends list to facilitate challenges, track online status, and foster a social community.
*   **Database:**
    *   A `friends` table would be required: `id`, `user_id` (requester), `friend_id` (recipient), `status` (enum: `pending`, `accepted`, `blocked`), `timestamps`.
*   **API Endpoints:**
    *   `POST /v1/friends`: Send a friend request. Body: `{ "user_id": "..." }`.
    *   `GET /v1/friends`: List all friends with their current status.
    *   `PATCH /v1/friends/{friendship_id}`: Accept or decline a pending request. Body: `{ "status": "accepted" }`.
    *   `DELETE /v1/friends/{friendship_id}`: Remove a friend or cancel a request.
*   **Integration:** The `POST /v1/matches` endpoint could be updated to allow `opponent_id` to be a friend's ID, initiating a direct challenge.

### Real-Time Match Chat

*   **Concept:** Enable players within an active match to send and receive messages in real-time.
*   **Technology:** This is a perfect use case for the existing **Laravel Reverb** WebSocket server.
*   **Implementation:**
    1.  **Private Channel:** Define a private broadcast channel for each match: `private-match.{ulid}`.
    2.  **Authorization:** In `routes/channels.php`, authorize users to join this channel only if they are a player in that specific match.
    3.  **New Endpoint:** Create `POST /v1/matches/{ulid}/chat`.
    4.  **Logic:** This endpoint's controller method would validate the message and then broadcast a `ChatMessageSent` event to the private match channel. All connected clients (players) in that match would receive the message instantly.

---

## 2. 🎮 Gameplay & Engagement Variety

### Tournaments

*   **Concept:** Allow users to participate in scheduled or on-demand tournaments with a structured bracket system (e.g., single-elimination).
*   **Database Schema:**
    *   `tournaments`: `id`, `name`, `game_slug`, `start_time`, `status` (`pending`, `active`, `completed`).
    *   `tournament_participants`: `id`, `tournament_id`, `user_id`.
    *   `tournament_brackets`: `id`, `tournament_id`, `round`, `match_id`, `player1_id`, `player2_id`, `winner_id`.
*   **Logic:**
    *   A service layer would be needed to manage tournament state.
    *   When a tournament starts, a job would generate the first-round brackets and create the initial `Match` records.
    *   As each match completes, a listener would update the bracket and, if applicable, create the next-round match for the winner.

### Asynchronous Gameplay (Turn-Based)

*   **Concept:** Support games where players do not need to be online simultaneously (e.g., Chess, turn-based strategy games).
*   **Implementation:** The current architecture is already well-suited for this. The key addition is a notification system to alert players when it's their turn.
    1.  **Push Notifications:** Integrate a service like Firebase Cloud Messaging (FCM) or Apple Push Notification Service (APNS).
    2.  **Device Tokens:** Store user device tokens in a `user_devices` table.
    3.  **Notification Event:** When the `MoveService` processes a move and it's the next player's turn, it dispatches a `NotifyPlayerOfTurn` event.
    4.  **Event Listener:** A listener for this event would then use the push notification service to send an alert to the opponent's registered devices.

---

## 3. 💰 Monetization & Store Features

### In-Game Store for Cosmetics

*   **Concept:** Create a store where users can make one-time purchases for cosmetic items like premium Avatars, custom game board skins, or unique chat emojis.
*   **Database:**
    *   `products`: `id`, `name`, `description`, `price`, `item_type` (e.g., 'avatar'), `item_id`.
    *   `user_inventory`: `id`, `user_id`, `product_id`, `purchase_date`.
*   **Billing Integration:**
    *   The `BillingService` would need to be expanded to handle one-time purchases through Stripe (`PaymentIntents`), Google Play, and the App Store.
    *   New endpoints would be required:
        *   `GET /v1/store/products`: List all available items for sale.
        *   `POST /v1/store/purchase`: Initiate a purchase for a specific product.

### Ticket System (Pay-per-Match)

*   **Concept:** Offer an alternative to subscriptions where users can buy a "pack" of matches (e.g., 10 tickets for $1.99). This is a great option for less frequent players.
*   **Database:**
    *   A simple `user_tickets` table: `user_id`, `ticket_balance`.
*   **Logic:**
    1.  **Purchase:** Use the same one-time purchase flow as the In-Game Store to allow users to buy ticket packs.
    2.  **Usage:** The `QuotaService` (or a similar service that runs before match creation) would be modified. If a user is out of free "strikes" and does not have a subscription, it would check for an available ticket in their balance.
    3.  **Deduct:** If a ticket is available, it is consumed, and the match is created. If not, the request is denied.
