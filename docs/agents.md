This document outlines the architecture for creating, scheduling, and deploying AI Agents within the GamerProtocol.io API. The core principle is that **every Agent is a `User`** with a special, attached `Agent` profile.

---

## 💾 I. Database & Model Structure

This approach uses a one-to-one relationship between `users` and `agents`, providing a clean separation of concerns while maintaining a unified player identity.

| Table | Column | Data Type | Purpose |
| :--- | :--- | :--- | :--- |
| **`users`** | `id` | `BIGINT` | Primary key for all players. |
| | `username` | `VARCHAR` | Unique handle for login and identification. |
| | `agent_id` | `BIGINT` (nullable, unique) | **The Link.** A foreign key to the `agents` table. If not `NULL`, this user is a bot. |
| **`agents`** | `id` | `BIGINT` | Primary key for the agent profile. |
| | `ai_logic_path` | `VARCHAR` | The class path to the AI strategy (e.g., `ValidateFourMinimax::class`). |
| | `available_hour_est` | `TINYINT` | The hour (0-23) the agent is available to play. |

---

## 💻 II. Agent Scheduling & Availability Logic

The logic focuses on finding a `User` who is an agent and is not currently busy.

### A. The Core Scheduling Service

A service class is responsible for finding an available agent.

**`app/Services/Agents/SchedulingService.php`**

| Method | Logic |
| :--- | :--- |
| **`findAvailableAgent(string $titleSlug): ?User`** | 1. **Get Current Hour (EST):** Determine the current hour (0-23) in the `America/New_York` timezone. 2. **Query for Eligible Agents:** Find a `User` where `agent_id` is NOT NULL. Join with the `agents` table to filter where `available_hour_est` matches the current hour. 3. **Filter for Busy Status:** From the eligible agents, filter out any who are currently in an active or pending game using the `isAgentBusy()` method. 4. **Return:** Return the first available, non-busy `User` model, or `null`. |
| **`isAgentBusy(User $agentUser): bool`** | Checks if the agent's `user_id` is present in the `players` table for any game with a status of `active` or `pending`. |

### B. Busy Check Logic Detail

The `isAgentBusy` method uses a simple and efficient query.

```php
// Logic inside SchedulingService::isAgentBusy(User $agentUser)

$isBusy = Game::whereIn('status', ['active', 'pending'])
    // Check if the agent's user_id is in any active/pending game
    ->whereHas('players', function ($query) use ($agentUser) {
        $query->where('user_id', $agentUser->id);
    })
    ->exists();

return $isBusy;
```

---

## 🔗 III. API & Game Service Integration

### A. Game Creation (`POST /v1/games`)

The controller uses the scheduling service to find an opponent.

```php
// In GameController::store()
$agentUser = $schedulingService->findAvailableAgent($request->title_slug);

if ($agentUser) {
    // Agent found, create the game with the human user and the agent user.
    $game = $this->createGame($request->title_slug, auth()->user(), $agentUser);
} else {
    // No agent is available at this time.
    return response()->json(['message' => 'No agents are available right now.'], 404);
}
```

### B. AI Move Generation (`getAIMove` method)

The game service uses the agent's profile to determine which AI logic to execute.

```php
// In a Game Service like ValidateFourService.php...
public function getAIMove(Game $game): array
{
    // Get the Player model for the current turn
    $currentPlayer = $game->players()->where('position_id', $game->turn_number)->first();
    
    // Get the associated User model
    $agentUser = $currentPlayer->user;

    // Check if the user is an agent and has a logic path
    if ($agentUser->isAgent() && $agentUser->agent->ai_logic_path) {
        // Get the attached Agent profile
        $agentProfile = $agentUser->agent;
        
        // Instantiate the strategy class from the profile
        $strategyClass = $agentProfile->ai_logic_path;
        $strategy = new $strategyClass();
        
        // Execute the algorithm
        return $strategy->calculateMove($game->game_state, $currentPlayer->position_id);
    }

    throw new \Exception("AI logic not found for this user.");
}
```

This unified architecture simplifies relationships, queries, and the overall mental model of the application, providing a robust foundation for all player types.