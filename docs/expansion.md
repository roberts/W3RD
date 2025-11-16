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
*   **Integration:** The `POST /v1/games` endpoint could be updated to allow `opponent_id` to be a friend's ID, initiating a direct challenge.

### Real-Time Game Chat

*   **Concept:** Enable players within an active game to send and receive messages in real-time.
*   **Technology:** This is a perfect use case for the existing **Laravel Reverb** WebSocket server.
*   **Implementation:**
    1.  **Private Channel:** Define a private broadcast channel for each game: `private-game.{ulid}`.
    2.  **Authorization:** In `routes/channels.php`, authorize users to join this channel only if they are a player in that specific game.
    3.  **New Endpoint:** Create `POST /v1/games/{ulid}/chat`.
    4.  **Logic:** This endpoint's controller method would validate the message and then broadcast a `ChatMessageSent` event to the private game channel. All connected clients (players) in that game would receive the message instantly.

---

## 2. 🎮 Gameplay & Engagement Variety

### Tournaments

*   **Concept:** Allow users to participate in scheduled or on-demand tournaments with a structured bracket system (e.g., single-elimination).
*   **Database Schema:**
    *   `tournaments`: `id`, `name`, `title_slug`, `start_time`, `status` (`pending`, `active`, `completed`).
    *   `tournament_participants`: `id`, `tournament_id`, `user_id`.
    *   `tournament_brackets`: `id`, `tournament_id`, `round`, `game_id`, `player1_id`, `player2_id`, `winner_id`.
*   **Logic:**
    *   A service layer would be needed to manage tournament state.
    *   When a tournament starts, a job would generate the first-round brackets and create the initial `Game` records.
    *   As each game completes, a listener would update the bracket and, if applicable, create the next-round game for the winner.

### Asynchronous Gameplay (Turn-Based)

*   **Concept:** Support game titles where players do not need to be online simultaneously (e.g., Chess, turn-based strategy games).
*   **Implementation:** The current architecture is already well-suited for this. The key addition is a notification system to alert players when it's their turn.
    1.  **Push Notifications:** Integrate a service like Firebase Cloud Messaging (FCM) or Apple Push Notification Service (APNS).
    2.  **Device Tokens:** Store user device tokens in a `user_devices` table.
    3.  **Notification Event:** When the `ActionService` processes an action and it's the next player's turn, it dispatches a `NotifyPlayerOfTurn` event.
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

### Ticket System (Pay-per-Game)

*   **Concept:** Offer an alternative to subscriptions where users can buy a "pack" of games (e.g., 10 tickets for $1.99). This is a great option for less frequent players.
*   **Database:**
    *   A simple `user_tickets` table: `user_id`, `ticket_balance`.
*   **Logic:**
    1.  **Purchase:** Use the same one-time purchase flow as the In-Game Store to allow users to buy ticket packs.
    2.  **Usage:** The `QuotaService` (or a similar service that runs before game creation) would be modified. If a user is out of free "strikes" and does not have a subscription, it would check for an available ticket in their balance.
    3.  **Deduct:** If a ticket is available, it is consumed, and the game is created. If not, the request is denied.

### Party/Group Queuing

*   **Concept:** Allow a party of two or more friends to queue up together for team-based game titles (e.g., 2v2 Spades).
*   **Implementation:**
    *   **Party System:** A more formal party system would be needed, likely managed in Redis. A party leader would invite friends, and once assembled, the leader could initiate the matchmaking search.
    *   **Matchmaking Logic:** The `ProcessMatchmakingQueue` job would be adapted to look for parties of the correct size instead of individuals. It would then match one party against another.
    *   **Data Structure:** The queue in Redis would need to store party IDs instead of just user IDs, with a separate Redis hash mapping party IDs to the list of user IDs in that party.

## 4. User Stats

Already have a few tables & need to rethink the best way to manage this data. Will return to this later.

- Lifetime Games Played
- Games Won
- Current Streak
- Same stats for each of the Game Titles & maybe the Modes.

## 5. Potential Table Additions

- Notifications - for Laravel's notification system
- Game Invites - if games can be private/invited
- Blocked Users - for user moderation
- Reported Content - for community management

## 6\. `create_clans_table` (Social Structure)

Defines persistent groups for collaboration and competition.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('leader_id')->constrained('users');
            $table->json('resource_pool_json')->nullable()->comment('Shared resources/bank');
            $table->timestamps();
        });
    }
};
```

## 7\. `create_clan_members_table` (Clan Pivot)

Links users to their clan and defines their role within the social structure.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clan_members', function (Blueprint $table) {
            $table->foreignId('clan_id')->constrained('clans');
            $table->foreignId('user_id')->constrained('users');
            $table->string('rank_title', 50)->default('Member');
            $table->primary(['clan_id', 'user_id']);
            $table->timestamps();
        });
    }
};
```

## 8\. `create_user_resources_table` (Persistent Inventory)

Tracks global resources for complex strategy games, separated by game type.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_resources', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->string('game_slug', 50); // The game this resource is tied to
            $table->string('resource_type', 50); // e.g., 'gold', 'wood', 'population'
            $table->decimal('quantity', 14, 4)->default(0);

            $table->primary(['user_id', 'game_slug', 'resource_type'], 'user_resource_pk');
            $table->timestamps();
        });
    }
};
```

## 9\. `create_skill_ratings_table` (ELO/MMR Tracking)

Tracks advanced matchmaking rating separate from experience level.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_ratings', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->string('game_slug', 50); 
            $table->integer('rating_value')->default(1500)->comment('Standard ELO or MMR rating');
            $table->integer('games_played')->default(0);

            $table->primary(['user_id', 'game_slug']);
            $table->timestamps();
        });
    }
};
```