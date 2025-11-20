# 🚀 GamerProtocol.io API Expansion Ideas

This document outlines potential new features to expand the GamerProtocol.io API beyond its core gameplay loop, focusing on community, monetization, and gameplay variety.

---

## 1. 🤝 Social & Community Features

### Friends System

*   **Concept:** Allow users to build a friends list to facilitate challenges, track online status, and foster a social community. This creates a social graph that is foundational for many engagement features.
*   **Database:**
    *   A `friendships` table would be required: `id`, `user_id` (requester), `friend_id` (recipient), `status` (enum: `pending`, `accepted`, `blocked`), `timestamps`.
*   **API Endpoints (RESTful approach):**
    *   `GET /v1/me/friends`: List the authenticated user's friends. Can be filtered by status (e.g., `?status=pending` to see incoming requests).
    *   `POST /v1/me/friends`: Send a friend request to another user.
        *   **Body:** `{ "user_id": "..." }`
    *   `PUT /v1/me/friends/{userId}`: Accept a pending friend request from the specified user.
    *   `DELETE /v1/me/friends/{userId}`: Remove a friend, decline a pending request, or cancel an outgoing request.
*   **Integration:**
    *   The `POST /v1/lobbies` endpoint could be updated to allow inviting friends directly.
    *   A new `POST /v1/challenges` endpoint could be created to directly challenge a friend to a game, creating a private lobby for both.
*   **Real-Time Events:**
    *   Use Laravel Reverb to push notifications on a private user channel (`private-user.{userId}`) for events like:
        *   `FriendRequestReceived`
        *   `FriendRequestAccepted`

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

---

## 10. Client & API Infrastructure

### Client Configuration Endpoint

*   **Concept:** Provide a dynamic, server-driven configuration for all connected clients. This allows for remote control of client-side features, validation rules, and other settings without requiring a new app deployment.
*   **API Endpoint:** `GET /v1/config`
*   **Implementation:**
    *   This unauthenticated endpoint would return a JSON object containing key-value pairs.
    *   The configuration can be stored in a simple Laravel `config` file or a database table for more dynamic control.
*   **Example Response:**
    ```json
    {
      "features": {
        "tournaments_enabled": true,
        "chat_enabled": false
      },
      "validation": {
        "username": {
          "min_length": 3,
          "max_length": 15
        }
      },
      "auth_providers": ["google", "apple"]
    }
    ```

---

## 11. Game Health Indicators

### Concept
Provide proactive visibility into connection quality, player responsiveness, and system health to help clients anticipate and handle issues that might affect gameplay.

### Implementation

#### Response Enhancements
Add health indicators to all game-related responses:

**GameResource additions:**
```json
{
  "ulid": "01JMQXXX",
  "status": "active",
  "health": {
    "server_latency_ms": 45,
    "last_action_timestamp": "2025-11-19T14:30:25Z",
    "action_queue_depth": 0,
    "players": [
      {
        "player_ulid": "01JMQYYY",
        "connection_quality": "excellent",
        "last_seen": "2025-11-19T14:30:20Z",
        "seconds_since_activity": 5,
        "average_response_time_ms": 2500,
        "approaching_timeout": false,
        "timeout_in_seconds": 25
      },
      {
        "player_ulid": "01JMQZZZ",
        "connection_quality": "poor",
        "last_seen": "2025-11-19T14:29:50Z",
        "seconds_since_activity": 35,
        "average_response_time_ms": 15000,
        "approaching_timeout": true,
        "timeout_in_seconds": 5,
        "slow_player_warning": true
      }
    ]
  }
}
```

#### Database Schema
Track player activity patterns:

```php
// Add to players table migration
$table->timestamp('last_action_at')->nullable();
$table->integer('average_response_time_ms')->nullable();
$table->integer('total_actions')->default(0);
```

New `player_activity_metrics` table:
```php
Schema::create('player_activity_metrics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->string('game_title_slug', 50);
    $table->integer('average_response_time_ms');
    $table->decimal('completion_rate', 5, 2)->comment('% of games completed');
    $table->integer('timeout_count')->default(0);
    $table->timestamp('last_calculated_at');
    $table->timestamps();
    
    $table->unique(['user_id', 'game_title_slug']);
});
```

#### Connection Quality Calculation
**Service:** `App\Services\GameHealthService`

```php
public function getConnectionQuality(Player $player): string
{
    $latency = $this->getPlayerLatency($player);
    $lastSeen = $player->last_action_at ?? now();
    $secondsSinceActivity = now()->diffInSeconds($lastSeen);
    
    // If idle too long, connection is poor
    if ($secondsSinceActivity > 60) {
        return 'disconnected';
    }
    
    if ($secondsSinceActivity > 30) {
        return 'poor';
    }
    
    // Check latency
    if ($latency < 100) {
        return 'excellent';
    } elseif ($latency < 300) {
        return 'good';
    } elseif ($latency < 1000) {
        return 'fair';
    } else {
        return 'poor';
    }
}

public function isApproachingTimeout(Player $player, Game $game): bool
{
    $deadline = $this->getActionDeadline($game);
    $secondsRemaining = now()->diffInSeconds($deadline, false);
    
    // Warn when less than 10 seconds remain
    return $secondsRemaining > 0 && $secondsRemaining < 10;
}
```

#### WebSocket Events
Broadcast health changes in real-time:

**Event:** `GameHealthUpdated`
```php
class GameHealthUpdated implements ShouldBroadcast
{
    public function __construct(
        public readonly Game $game,
        public readonly string $playerUlid,
        public readonly string $connectionQuality,
        public readonly bool $slowPlayerWarning,
    ) {}
    
    public function broadcastOn(): Channel
    {
        return new Channel("game.{$this->game->ulid}");
    }
    
    public function broadcastAs(): string
    {
        return 'health.updated';
    }
}
```

#### API Endpoints

**New endpoint:** `GET /v1/games/{ulid}/health`
Returns detailed health metrics for the game and all players.

**Response format:**
```json
{
  "game_ulid": "01JMQXXX",
  "server_health": {
    "status": "operational",
    "latency_ms": 45,
    "websocket_connected": true,
    "action_processing_delay_ms": 12
  },
  "players": [...],
  "warnings": [
    {
      "type": "slow_player",
      "player_ulid": "01JMQZZZ",
      "message": "Player 2 has been idle for 35 seconds",
      "severity": "warning"
    }
  ]
}
```

#### Integration Points

1. **Action Processing:** Update `last_action_at` and calculate rolling average response time
2. **WebSocket Heartbeat:** Track connection status via ping/pong
3. **Timeout Handler:** Use health metrics to decide when to force timeout
4. **Matchmaking:** Consider player reliability when creating matches

#### Client Usage

```javascript
// Subscribe to health updates
Echo.channel(`game.${gameUlid}`)
  .listen('.health.updated', (event) => {
    if (event.slowPlayerWarning) {
      showNotification('Your opponent may be experiencing connection issues');
    }
    
    updateConnectionIndicator(event.connectionQuality);
  });

// Poll health endpoint for current status
async function checkGameHealth(gameUlid) {
  const health = await fetch(`/api/v1/games/${gameUlid}/health`);
  
  if (health.warnings.length > 0) {
    displayWarnings(health.warnings);
  }
  
  // Show latency indicator
  updateLatencyDisplay(health.server_health.latency_ms);
}
```

#### Benefits

- **Proactive Issue Detection:** Warn players before timeouts occur
- **Better UX:** Explain delays ("Waiting for opponent - connection issues detected")
- **Reduced Frustration:** Players understand when issues are technical vs intentional
- **Support Data:** Health metrics help diagnose player-reported issues
- **Matchmaking Quality:** Avoid pairing high-latency players in fast-paced games

---

## 12. Enhance ValidationResult with Suggested Alternatives

### Concept
When actions are invalid, provide actionable suggestions showing users what they SHOULD do instead, turning errors into learning opportunities and improving UX.

### Implementation

#### Expand ValidationResult Class
**File:** `app/Games/ValidationResult.php`

```php
class ValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly ?string $errorCode = null,
        public readonly ?string $message = null,
        public readonly array $context = [],
        public readonly ?string $severity = 'error',
        public readonly array $suggestedActions = [],  // NEW
        public readonly array $helpfulHints = [],       // NEW
    ) {}
    
    public static function invalid(
        string $errorCode, 
        string $message, 
        array $context = [],
        array $suggestedActions = [],
        array $helpfulHints = []
    ): self {
        return new self(
            false, 
            $errorCode, 
            $message, 
            $context,
            'error',
            $suggestedActions,
            $helpfulHints
        );
    }
}
```

#### Game-Specific Implementations

**Connect Four (Connect 4):**
```php
// When column is full
if ($lowestEmptyRow === null) {
    $availableColumns = [];
    for ($col = 0; $col < $gameState->columns; $col++) {
        if ($gameState->getLowestEmptyRow($col) !== null) {
            $availableColumns[] = $col;
        }
    }
    
    return ValidationResult::invalid(
        'column_full',
        sprintf('Column %d is full', $action->column),
        [
            'column' => $action->column,
            'filled_rows' => $gameState->rows,
        ],
        suggestedActions: [
            [
                'action_type' => 'drop_piece',
                'columns' => $availableColumns,
                'reason' => 'These columns have space available',
            ],
        ],
        helpfulHints: [
            sprintf('Columns %s are available', implode(', ', array_map(fn($c) => $c + 1, $availableColumns))),
            'Try blocking your opponent\'s potential winning move',
        ]
    );
}
```

**Checkers:**
```php
// When trying to move a piece that must jump
protected function validateMovePiece(GameState $gameState, Actions\MovePiece $action): ValidationResult
{
    $piece = $gameState->getPieceAt($action->fromRow, $action->fromCol);
    
    // Check if any jumps are available for this player
    $availableJumps = $this->findAvailableJumps($gameState, $gameState->currentPlayerUlid);
    
    if (!empty($availableJumps)) {
        $jumpOptions = array_map(function($jump) {
            return [
                'action_type' => 'jump_piece',
                'from' => ['row' => $jump['fromRow'], 'col' => $jump['fromCol']],
                'to' => ['row' => $jump['toRow'], 'col' => $jump['toCol']],
                'captures' => ['row' => $jump['captureRow'], 'col' => $jump['captureCol']],
            ];
        }, $availableJumps);
        
        return ValidationResult::invalid(
            'must_jump',
            'You must jump when a jump is available',
            [
                'attempted_move' => [
                    'from' => ['row' => $action->fromRow, 'col' => $action->fromCol],
                    'to' => ['row' => $action->toRow, 'col' => $action->toCol],
                ],
                'available_jump_count' => count($availableJumps),
            ],
            suggestedActions: $jumpOptions,
            helpfulHints: [
                'In Checkers, capturing is mandatory when available',
                'You can capture ' . count($availableJumps) . ' opponent piece(s) this turn',
            ]
        );
    }
    
    // ... rest of validation
}
```

**Hearts:**
```php
// When must follow suit
if (!$this->canFollowSuit($gameState, $action->card)) {
    $leadSuit = $this->getLeadSuit($gameState->currentTrick);
    $playerHand = $gameState->hands[$gameState->currentPlayerUlid];
    $cardsInSuit = array_filter($playerHand, fn($card) => $card[0] === $leadSuit);
    
    $suggestedCards = array_map(function($card) {
        return [
            'action_type' => 'play_card',
            'card' => $card,
            'card_name' => $this->getCardName($card),
            'reason' => 'Follows suit requirement',
        ];
    }, $cardsInSuit);
    
    return ValidationResult::invalid(
        'must_follow_suit',
        sprintf('You must follow suit when able. Lead suit is %s', $this->getSuitName($leadSuit)),
        [
            'card_played' => $action->card,
            'lead_suit' => $leadSuit,
            'cards_in_suit_count' => count($cardsInSuit),
        ],
        suggestedActions: $suggestedCards,
        helpfulHints: [
            sprintf('You have %d %s in your hand', count($cardsInSuit), $this->getSuitName($leadSuit)),
            'Only play off-suit when you have no cards matching the lead suit',
        ]
    );
}

// When hearts not broken
if (!$gameState->heartsBroken && $action->isHeart() && $this->hasNonHearts($gameState)) {
    $nonHeartCards = array_filter(
        $gameState->hands[$gameState->currentPlayerUlid],
        fn($card) => $card[0] !== 'H'
    );
    
    $suggestedCards = array_map(function($card) {
        return [
            'action_type' => 'play_card',
            'card' => $card,
            'card_name' => $this->getCardName($card),
            'reason' => 'Hearts are not broken yet',
        ];
    }, array_values($nonHeartCards));
    
    return ValidationResult::invalid(
        'hearts_not_broken',
        'You cannot lead with a heart until hearts have been broken',
        [
            'card_played' => $action->card,
            'hearts_broken' => false,
            'alternative_count' => count($nonHeartCards),
        ],
        suggestedActions: $suggestedCards,
        helpfulHints: [
            'Hearts are "broken" when someone plays a heart because they cannot follow suit',
            sprintf('You have %d non-heart cards you can play', count($nonHeartCards)),
        ]
    );
}
```

#### API Response Format

**Error response with suggestions:**
```json
{
  "message": "Column 3 is full",
  "error_code": "column_full",
  "game_title": "connect-four",
  "severity": "error",
  "context": {
    "column": 3,
    "filled_rows": 6
  },
  "suggested_actions": [
    {
      "action_type": "drop_piece",
      "columns": [0, 1, 2, 4, 5, 6],
      "reason": "These columns have space available"
    }
  ],
  "helpful_hints": [
    "Columns 1, 2, 3, 5, 6, 7 are available",
    "Try blocking your opponent's potential winning move"
  ]
}
```

#### Client Usage

```javascript
async function submitAction(gameUlid, actionType, actionData) {
  try {
    const response = await fetch(`/api/v1/games/${gameUlid}/actions`, {
      method: 'POST',
      body: JSON.stringify({ action_type: actionType, ...actionData })
    });
    
    if (!response.ok) {
      const error = await response.json();
      
      if (error.suggested_actions && error.suggested_actions.length > 0) {
        // Show interactive suggestions
        showActionSuggestions(error.suggested_actions, error.helpful_hints);
        
        // Highlight valid positions on the board
        highlightValidMoves(error.suggested_actions);
      } else {
        // Fallback to simple error message
        showError(error.message);
      }
    }
  } catch (err) {
    showError('Network error');
  }
}

function showActionSuggestions(suggestions, hints) {
  // Display as interactive buttons/options
  const suggestionPanel = document.getElementById('suggestion-panel');
  suggestionPanel.innerHTML = `
    <div class="suggestions">
      <h3>Try instead:</h3>
      ${suggestions.map(action => `
        <button onclick="submitAction('${gameUlid}', '${action.action_type}', ${JSON.stringify(action)})">
          ${formatActionDescription(action)}
        </button>
      `).join('')}
      
      <div class="hints">
        ${hints.map(hint => `<p class="hint">💡 ${hint}</p>`).join('')}
      </div>
    </div>
  `;
}
```

#### Helper Methods

Add to base game classes:

```php
// app/Games/ConnectFour/BaseConnectFour.php
protected function getAvailableColumnsWithContext(GameState $gameState): array
{
    $columns = [];
    for ($col = 0; $col < $gameState->columns; $col++) {
        $lowestRow = $gameState->getLowestEmptyRow($col);
        if ($lowestRow !== null) {
            $columns[] = [
                'column' => $col,
                'empty_spaces' => $lowestRow + 1,
                'would_win' => $this->wouldWin($gameState, $col),
                'blocks_opponent' => $this->blocksOpponentWin($gameState, $col),
            ];
        }
    }
    return $columns;
}

// app/Games/Checkers/BaseCheckers.php
protected function findAvailableJumps(GameState $gameState, string $playerUlid): array
{
    $jumps = [];
    for ($row = 0; $row < 8; $row++) {
        for ($col = 0; $col < 8; $col++) {
            $piece = $gameState->getPieceAt($row, $col);
            if ($piece && $piece['player'] === $playerUlid) {
                $jumps = array_merge($jumps, $this->findJumpsForPiece($gameState, $row, $col));
            }
        }
    }
    return $jumps;
}
```

#### Update Documentation

Add to `docs/actionerrors.md`:

```markdown
## Suggested Actions in Error Responses

When validation fails, the API provides actionable suggestions:

### Structure
- `suggested_actions`: Array of alternative valid actions
- `helpful_hints`: Array of educational tips and strategy hints

### Example - Column Full
```json
{
  "suggested_actions": [
    {
      "action_type": "drop_piece",
      "columns": [0, 1, 2, 4, 5],
      "reason": "These columns have space available"
    }
  ],
  "helpful_hints": [
    "Try blocking your opponent's potential winning move",
    "Column 4 would create a threat"
  ]
}
```

### Client Integration
Clients should:
1. Display suggestions as interactive options
2. Highlight valid positions on game board
3. Show hints to educate players
4. Allow quick retry with suggested action
```

#### Benefits

- **Reduced Frustration:** Players immediately know what they CAN do
- **Faster Learning:** Educational hints teach game rules contextually
- **Better UX:** Turn errors into opportunities
- **Fewer Support Tickets:** Self-service problem resolution
- **Engagement:** Players feel guided rather than blocked

---

## 13. Player Stats and Reputation in Context

### Concept
Enhance player information with gameplay statistics and reputation metrics to help players understand who they're playing against and set appropriate expectations.

### Database Schema

**Expand existing tables:**

```php
// Add to users table migration
$table->integer('games_played')->default(0);
$table->integer('games_won')->default(0);
$table->integer('games_abandoned')->default(0);
$table->integer('current_streak')->default(0);
$table->string('skill_level')->default('beginner'); // beginner, intermediate, advanced, expert
$table->decimal('completion_rate', 5, 2)->default(100.00);
$table->timestamp('first_game_at')->nullable();
$table->timestamp('last_game_at')->nullable();
```

**New `user_game_stats` table:**
```php
Schema::create('user_game_stats', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->string('game_title_slug', 50);
    $table->integer('games_played')->default(0);
    $table->integer('games_won')->default(0);
    $table->integer('games_lost')->default(0);
    $table->integer('games_drawn')->default(0);
    $table->integer('games_abandoned')->default(0);
    $table->integer('current_streak')->default(0);
    $table->integer('longest_streak')->default(0);
    $table->integer('average_response_time_ms')->nullable();
    $table->integer('fastest_win_turns')->nullable();
    $table->decimal('win_rate', 5, 2)->default(0);
    $table->timestamp('last_played_at')->nullable();
    $table->timestamps();
    
    $table->unique(['user_id', 'game_title_slug']);
});
```

**New `user_game_mode_stats` table:**
```php
Schema::create('user_game_mode_stats', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->foreignId('mode_id')->constrained('modes');
    $table->integer('games_played')->default(0);
    $table->integer('games_won')->default(0);
    $table->decimal('win_rate', 5, 2)->default(0);
    $table->timestamps();
    
    $table->unique(['user_id', 'mode_id']);
});
```

**New `user_achievements` table:**
```php
Schema::create('user_achievements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->string('achievement_slug', 50);
    $table->timestamp('earned_at');
    $table->json('metadata')->nullable()->comment('Additional achievement context');
    $table->timestamps();
    
    $table->unique(['user_id', 'achievement_slug']);
});
```

#### Service Layer

**StatsService:**
```php
namespace App\Services;

class StatsService
{
    public function updateUserStats(User $user, Game $game, bool $won): void
    {
        // Update global stats
        $user->increment('games_played');
        if ($won) {
            $user->increment('games_won');
            $user->increment('current_streak');
        } else {
            $user->update(['current_streak' => 0]);
        }
        
        $user->completion_rate = ($user->games_played - $user->games_abandoned) 
            / $user->games_played * 100;
        $user->last_game_at = now();
        $user->save();
        
        // Update game-specific stats
        $this->updateGameTitleStats($user, $game->title_slug, $won);
        $this->updateGameModeStats($user, $game->mode_id, $won);
        
        // Check for achievements
        $this->checkAchievements($user, $game);
    }
    
    protected function updateGameTitleStats(User $user, GameTitle $titleSlug, bool $won): void
    {
        $stats = UserGameStats::firstOrCreate([
            'user_id' => $user->id,
            'game_title_slug' => $titleSlug->value,
        ]);
        
        $stats->increment('games_played');
        if ($won) {
            $stats->increment('games_won');
            $stats->increment('current_streak');
            
            if ($stats->current_streak > $stats->longest_streak) {
                $stats->longest_streak = $stats->current_streak;
            }
        } else {
            $stats->update(['current_streak' => 0]);
            $stats->increment('games_lost');
        }
        
        $stats->win_rate = ($stats->games_won / $stats->games_played) * 100;
        $stats->last_played_at = now();
        $stats->save();
    }
    
    public function getPlayerReputation(User $user): array
    {
        $recentGames = Game::whereHas('players', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->latest()->limit(10)->get();
        
        $avgResponseTime = $this->calculateAverageResponseTime($user);
        $playstyle = $this->analyzePlaystyle($user);
        
        return [
            'experience_level' => $this->calculateExperienceLevel($user),
            'reliability' => [
                'completion_rate' => $user->completion_rate,
                'rating' => $this->getReliabilityRating($user->completion_rate),
            ],
            'activity' => [
                'games_played' => $user->games_played,
                'last_active' => $user->last_game_at?->diffForHumans(),
                'average_response_time_ms' => $avgResponseTime,
                'response_rating' => $this->getResponseTimeRating($avgResponseTime),
            ],
            'performance' => [
                'total_wins' => $user->games_won,
                'current_streak' => $user->current_streak,
                'skill_level' => $user->skill_level,
            ],
            'playstyle' => $playstyle,
            'badges' => $this->getUserBadges($user),
        ];
    }
    
    protected function analyzePlaystyle(User $user): array
    {
        // Analyze game history to determine playstyle
        $recentActions = Action::whereHas('game.players', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->latest()->limit(100)->get();
        
        $avgThinkTime = $recentActions->avg(function($action) {
            return $action->created_at->diffInSeconds($action->game->last_action_at ?? $action->game->created_at);
        });
        
        return [
            'pace' => $avgThinkTime < 5 ? 'fast' : ($avgThinkTime < 15 ? 'moderate' : 'thoughtful'),
            'aggression' => $this->calculateAggression($recentActions),
            'consistency' => $this->calculateConsistency($user),
        ];
    }
    
    protected function getUserBadges(User $user): array
    {
        $badges = [];
        
        // New player badge
        if ($user->games_played < 10) {
            $badges[] = [
                'slug' => 'new_player',
                'name' => 'New Player',
                'icon' => '🌱',
                'description' => 'Still learning the ropes',
            ];
        }
        
        // Veteran badge
        if ($user->games_played >= 100) {
            $badges[] = [
                'slug' => 'veteran',
                'name' => 'Veteran',
                'icon' => '🎖️',
                'description' => '100+ games played',
            ];
        }
        
        // Reliable badge
        if ($user->completion_rate >= 95 && $user->games_played >= 20) {
            $badges[] = [
                'slug' => 'reliable',
                'name' => 'Reliable',
                'icon' => '✅',
                'description' => 'Rarely abandons games',
            ];
        }
        
        // Speed demon badge
        $avgResponse = $this->calculateAverageResponseTime($user);
        if ($avgResponse < 3000 && $user->games_played >= 10) {
            $badges[] = [
                'slug' => 'speed_demon',
                'name' => 'Speed Demon',
                'icon' => '⚡',
                'description' => 'Lightning-fast moves',
            ];
        }
        
        return $badges;
    }
}
```

#### API Integration

**Enhanced PlayerResource:**
```php
namespace App\Http\Resources;

class PlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $includeStats = $request->get('include_stats', false);
        
        $data = [
            'username' => $this->user->username,
            'name' => $this->user->name,
            'position_id' => $this->position_id,
            'color' => $this->color,
            'avatar' => $this->user->avatar?->image?->url,
        ];
        
        if ($includeStats) {
            $statsService = app(StatsService::class);
            $data['reputation'] = $statsService->getPlayerReputation($this->user);
            
            // Game-specific stats
            $data['game_stats'] = UserGameStats::where([
                'user_id' => $this->user->id,
                'game_title_slug' => $this->game->title_slug->value,
            ])->first()?->toArray();
        }
        
        return $data;
    }
}
```

**New endpoint:** `GET /v1/users/{username}/stats`

Response format:
```json
{
  "user": {
    "username": "alice",
    "name": "Alice"
  },
  "global_stats": {
    "games_played": 156,
    "games_won": 89,
    "win_rate": 57.05,
    "current_streak": 3,
    "completion_rate": 97.44,
    "skill_level": "advanced",
    "member_since": "2024-08-15T10:30:00Z"
  },
  "reputation": {
    "experience_level": "advanced",
    "reliability": {
      "completion_rate": 97.44,
      "rating": "excellent"
    },
    "activity": {
      "games_played": 156,
      "last_active": "2 hours ago",
      "average_response_time_ms": 4500,
      "response_rating": "moderate"
    },
    "performance": {
      "total_wins": 89,
      "current_streak": 3,
      "skill_level": "advanced"
    },
    "playstyle": {
      "pace": "moderate",
      "aggression": "balanced",
      "consistency": "high"
    },
    "badges": [
      {
        "slug": "veteran",
        "name": "Veteran",
        "icon": "🎖️",
        "description": "100+ games played"
      },
      {
        "slug": "reliable",
        "name": "Reliable",
        "icon": "✅",
        "description": "Rarely abandons games"
      }
    ]
  },
  "by_game": {
    "connect-four": {
      "games_played": 45,
      "games_won": 28,
      "win_rate": 62.22,
      "current_streak": 2,
      "longest_streak": 7,
      "fastest_win_turns": 12
    },
    "checkers": {
      "games_played": 67,
      "games_won": 38,
      "win_rate": 56.72,
      "current_streak": 0,
      "longest_streak": 5
    },
    "hearts": {
      "games_played": 44,
      "games_won": 23,
      "win_rate": 52.27,
      "current_streak": 1,
      "longest_streak": 4
    }
  },
  "recent_achievements": [
    {
      "achievement_slug": "first_king_promotion",
      "earned_at": "2025-11-15T14:22:00Z",
      "game_title": "checkers"
    }
  ]
}
```

#### GameResource Enhancement

Include player stats in game responses when requested:

```php
// GET /v1/games/{ulid}?include_player_stats=true
{
  "ulid": "01JMQXXX",
  "status": "active",
  "players": [
    {
      "username": "alice",
      "position_id": 1,
      "reputation": {
        "experience_level": "advanced",
        "badges": ["veteran", "reliable"],
        "win_rate": 57.05,
        "avg_response_time": "fast"
      }
    },
    {
      "username": "bob",
      "position_id": 2,
      "reputation": {
        "experience_level": "beginner",
        "badges": ["new_player"],
        "win_rate": 45.50,
        "avg_response_time": "thoughtful"
      }
    }
  ]
}
```

#### Client Usage

```javascript
// Display player cards with stats
function displayOpponentInfo(player) {
  const badges = player.reputation.badges.map(b => b.icon).join(' ');
  
  return `
    <div class="opponent-card">
      <img src="${player.avatar}" />
      <h3>${player.username} ${badges}</h3>
      <div class="stats">
        <span class="level">${player.reputation.experience_level}</span>
        <span class="win-rate">${player.reputation.performance.win_rate}% wins</span>
        <span class="streak">🔥 ${player.reputation.performance.current_streak} streak</span>
      </div>
      <div class="playstyle">
        <span>Pace: ${player.reputation.playstyle.pace}</span>
        <span>Style: ${player.reputation.playstyle.aggression}</span>
      </div>
    </div>
  `;
}

// Show warnings for player behavior
if (opponent.reputation.reliability.completion_rate < 80) {
  showWarning('This player has a history of abandoning games');
}

if (opponent.reputation.badges.includes('new_player')) {
  showTip('Your opponent is new - consider being patient!');
}
```

#### Event Tracking

Update stats automatically via listeners:

```php
class UpdatePlayerStats
{
    public function handle(GameCompleted $event): void
    {
        $game = $event->game;
        $statsService = app(StatsService::class);
        
        foreach ($game->players as $player) {
            $won = $player->ulid === $event->winnerUlid;
            $statsService->updateUserStats($player->user, $game, $won);
        }
    }
}
```

#### Benefits

- **Informed Matchmaking:** Players know opponent skill level
- **Set Expectations:** See if opponent is fast or thoughtful
- **Social Proof:** Badges and stats build credibility
- **Motivation:** Players work toward achievements
- **Better UX:** "New player" badges encourage patience
- **Anti-Abuse:** Completion rates discourage abandonment

---

## 14. Add Game History Context to Error Responses

### Concept
When errors occur, provide recent game context so players understand the current situation and why their action failed, reducing confusion and frustration.

### Implementation

#### Expand GameActionDeniedException

**File:** `app/Exceptions/GameActionDeniedException.php`

```php
public function __construct(
    string $message,
    string $errorCode,
    string $gameTitle,
    string $severity = 'error',
    array $context = [],
    array $recentHistory = [],  // NEW
    array $gameContext = [],    // NEW
) {
    $this->context = array_merge($context, [
        'recent_history' => $recentHistory,
        'game_context' => $gameContext,
    ]);
    // ... rest of constructor
}
```

#### Service for History Context

**New service:** `App\Services\GameContextService`

```php
namespace App\Services;

class GameContextService
{
    public function getRecentHistory(Game $game, int $actionCount = 3): array
    {
        $recentActions = Action::where('game_id', $game->id)
            ->with('player.user:id,username')
            ->orderBy('created_at', 'desc')
            ->limit($actionCount)
            ->get()
            ->reverse()
            ->values();
        
        return $recentActions->map(function($action) {
            return [
                'turn_number' => $action->turn_number,
                'player' => $action->player->user->username,
                'player_position' => $action->player->position_id,
                'action_type' => $action->action_type->value,
                'action_summary' => $this->formatActionSummary($action),
                'timestamp' => $action->created_at->toIso8601String(),
                'seconds_ago' => $action->created_at->diffInSeconds(now()),
            ];
        })->toArray();
    }
    
    public function getGameContext(Game $game, object $gameState): array
    {
        return match($game->title_slug->value) {
            'connect-four' => $this->getConnectFourContext($game, $gameState),
            'checkers' => $this->getCheckersContext($game, $gameState),
            'hearts' => $this->getHeartsContext($game, $gameState),
            default => [],
        };
    }
    
    protected function getConnectFourContext(Game $game, $gameState): array
    {
        return [
            'turn_number' => $game->turn_number,
            'total_pieces_played' => $this->countPiecesOnBoard($gameState),
            'columns_available' => $this->countAvailableColumns($gameState),
            'near_win' => [
                'player_1' => $this->detectNearWin($gameState, 1),
                'player_2' => $this->detectNearWin($gameState, 2),
            ],
        ];
    }
    
    protected function getCheckersContext(Game $game, $gameState): array
    {
        $player1Ulid = array_keys($gameState->players)[0];
        $player2Ulid = array_keys($gameState->players)[1];
        
        return [
            'turn_number' => $game->turn_number,
            'pieces_remaining' => [
                'player_1' => $gameState->players[$player1Ulid]->piecesRemaining,
                'player_2' => $gameState->players[$player2Ulid]->piecesRemaining,
            ],
            'kings' => [
                'player_1' => $this->countKings($gameState, $player1Ulid),
                'player_2' => $this->countKings($gameState, $player2Ulid),
            ],
            'jump_available' => $this->hasAvailableJumps($gameState),
        ];
    }
    
    protected function getHeartsContext(Game $game, $gameState): array
    {
        return [
            'round_number' => $gameState->roundNumber,
            'trick_number' => $this->calculateTrickNumber($gameState),
            'phase' => $gameState->phase->value,
            'hearts_broken' => $gameState->heartsBroken,
            'passing_direction' => $this->getPassingDirection($gameState->roundNumber),
            'scores' => array_map(
                fn($player) => $player->score,
                $gameState->players
            ),
            'cards_in_trick' => count($gameState->currentTrick),
            'cards_remaining_in_hand' => count($gameState->hands[$gameState->currentPlayerUlid] ?? []),
        ];
    }
    
    protected function formatActionSummary(Action $action): string
    {
        return match($action->action_type->value) {
            'drop_piece' => sprintf(
                'dropped piece in column %d',
                $action->action_details['column'] + 1
            ),
            'pop_out' => sprintf(
                'popped out from column %d',
                $action->action_details['column'] + 1
            ),
            'move_piece' => sprintf(
                'moved from [%d,%d] to [%d,%d]',
                $action->action_details['from_row'],
                $action->action_details['from_col'],
                $action->action_details['to_row'],
                $action->action_details['to_col']
            ),
            'jump_piece' => sprintf(
                'jumped and captured at [%d,%d]',
                $action->action_details['captured_row'],
                $action->action_details['captured_col']
            ),
            'play_card' => sprintf(
                'played %s',
                $this->formatCard($action->action_details['card'])
            ),
            'pass_cards' => sprintf(
                'passed %d cards',
                count($action->action_details['cards'])
            ),
            default => $action->action_type->value,
        };
    }
}
```

#### Integration with Error Handling

**Update GamePlayerAuthorization trait:**

```php
protected function authorizePlayerTurn(Player $player, string $currentPlayerUlid): ?JsonResponse
{
    if ($currentPlayerUlid !== $player->ulid) {
        $contextService = app(GameContextService::class);
        $mode = GameServiceProvider::getMode($player->game);
        $gameState = $mode->getGameState();
        
        /** @var Player|null $currentPlayer */
        $currentPlayer = $player->game->players()->where('ulid', $currentPlayerUlid)->first();
        
        throw new GameActionDeniedException(
            'It is not your turn.',
            GameErrorCode::NOT_PLAYER_TURN->value,
            $player->game->title_slug->value,
            'error',
            [
                'current_turn' => [
                    'player_ulid' => $currentPlayerUlid,
                    'player_username' => $currentPlayer?->user->username ?? 'Unknown',
                    'player_position' => $currentPlayer?->position_id,
                ],
                'your_info' => [
                    'player_ulid' => $player->ulid,
                    'player_username' => $player->user->username,
                    'player_position' => $player->position_id,
                ],
                'turn_number' => $player->game->turn_number ?? 1,
            ],
            recentHistory: $contextService->getRecentHistory($player->game, 5),
            gameContext: $contextService->getGameContext($player->game, $gameState)
        );
    }

    return null;
}
```

**Update validation error handling:**

```php
// In GameActionController::store()
if (! $validationResult->isValid) {
    $contextService = app(GameContextService::class);
    
    $this->actionRecorder->recordFailure(
        $game,
        $player,
        $action,
        $validationResult,
        $game->turn_number ?? 1
    );
    
    throw new GameActionDeniedException(
        $validationResult->message,
        strtolower($validationResult->errorCode),
        $game->title_slug->value,
        $validationResult->severity ?? 'error',
        $validationResult->context ?? [],
        recentHistory: $contextService->getRecentHistory($game),
        gameContext: $contextService->getGameContext($game, $gameState)
    );
}
```

#### API Response Format

**Error with game context:**

```json
{
  "message": "You must jump when a jump is available",
  "error_code": "must_jump",
  "game_title": "checkers",
  "severity": "error",
  "context": {
    "attempted_move": {
      "from": {"row": 3, "col": 2},
      "to": {"row": 4, "col": 3}
    },
    "available_jump_count": 2,
    "recent_history": [
      {
        "turn_number": 18,
        "player": "bob",
        "player_position": 2,
        "action_type": "move_piece",
        "action_summary": "moved from [5,4] to [4,5]",
        "timestamp": "2025-11-19T14:30:15Z",
        "seconds_ago": 8
      },
      {
        "turn_number": 19,
        "player": "alice",
        "player_position": 1,
        "action_type": "move_piece",
        "action_summary": "moved from [2,3] to [3,2]",
        "timestamp": "2025-11-19T14:30:20Z",
        "seconds_ago": 3
      }
    ],
    "game_context": {
      "turn_number": 20,
      "pieces_remaining": {
        "player_1": 8,
        "player_2": 7
      },
      "kings": {
        "player_1": 2,
        "player_2": 1
      },
      "jump_available": true
    }
  }
}
```

**Hearts phase error with context:**

```json
{
  "message": "This action is not valid in the current game phase.",
  "error_code": "invalid_game_phase",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "current_phase": "playing",
    "required_phase": "passing",
    "action_type": "pass_cards",
    "recent_history": [
      {
        "turn_number": 45,
        "player": "alice",
        "player_position": 1,
        "action_type": "play_card",
        "action_summary": "played 2♣",
        "timestamp": "2025-11-19T14:28:30Z",
        "seconds_ago": 93
      },
      {
        "turn_number": 46,
        "player": "bob",
        "player_position": 2,
        "action_type": "play_card",
        "action_summary": "played 3♣",
        "timestamp": "2025-11-19T14:28:45Z",
        "seconds_ago": 78
      },
      {
        "turn_number": 47,
        "player": "carol",
        "player_position": 3,
        "action_type": "play_card",
        "action_summary": "played K♣",
        "timestamp": "2025-11-19T14:29:00Z",
        "seconds_ago": 63
      }
    ],
    "game_context": {
      "round_number": 2,
      "trick_number": 12,
      "phase": "playing",
      "hearts_broken": true,
      "passing_direction": "right",
      "scores": {
        "player_1": 15,
        "player_2": 8,
        "player_3": 22,
        "player_4": 3
      },
      "cards_in_trick": 3,
      "cards_remaining_in_hand": 2
    }
  }
}
```

#### Client Usage

```javascript
function displayError(error) {
  const container = document.getElementById('error-container');
  
  // Show main error message
  container.innerHTML = `
    <div class="error-message">
      <h3>${error.message}</h3>
    </div>
  `;
  
  // Show recent history for context
  if (error.context.recent_history && error.context.recent_history.length > 0) {
    const historyHtml = `
      <div class="recent-history">
        <h4>Recent Moves:</h4>
        <ul>
          ${error.context.recent_history.map(action => `
            <li>
              <span class="player">Turn ${action.turn_number}: ${action.player}</span>
              <span class="action">${action.action_summary}</span>
              <span class="time">${action.seconds_ago}s ago</span>
            </li>
          `).join('')}
        </ul>
      </div>
    `;
    container.innerHTML += historyHtml;
  }
  
  // Show game context
  if (error.context.game_context) {
    const contextHtml = formatGameContext(error.game_title, error.context.game_context);
    container.innerHTML += contextHtml;
  }
}

function formatGameContext(gameTitle, context) {
  switch(gameTitle) {
    case 'hearts':
      return `
        <div class="game-context">
          <h4>Current Game State:</h4>
          <p>Round ${context.round_number}, Trick ${context.trick_number} of 13</p>
          <p>Hearts ${context.hearts_broken ? 'are' : 'are not'} broken</p>
          <p>You have ${context.cards_remaining_in_hand} cards left</p>
        </div>
      `;
    case 'checkers':
      return `
        <div class="game-context">
          <h4>Current Game State:</h4>
          <p>Turn ${context.turn_number}</p>
          <p>Pieces: You ${context.pieces_remaining.player_1} - Opponent ${context.pieces_remaining.player_2}</p>
          <p>Kings: You ${context.kings.player_1} - Opponent ${context.kings.player_2}</p>
          ${context.jump_available ? '<p class="warning">⚠️ You must jump!</p>' : ''}
        </div>
      `;
    default:
      return '';
  }
}
```

#### Benefits

- **Context Clarity:** Players see what led to the error
- **Reduced Confusion:** History explains current game state
- **Better UX:** Players don't feel lost mid-game
- **Educational:** Learn from recent moves
- **Support Quality:** More info for debugging issues
- **Engagement:** Players stay oriented in complex games