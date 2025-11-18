# V2 Agent Architecture

This document outlines the comprehensive architecture for creating, scheduling, and deploying AI Agents. The core principle is that **every Agent is a `User`** with a special, attached `Agent` profile, making them indistinguishable from human players.

This design focuses on realism, configurability, and scalability.

---

## 💾 I. Database & Model Structure

A one-to-one relationship between `users` and `agents` provides a clean separation of concerns while maintaining a unified player identity.

| Table | Column | Data Type | Purpose |
| :--- | :--- | :--- | :--- |
| **`users`** | `id` | `BIGINT` | Primary key for all players. |
| | `username` | `VARCHAR` | Unique handle for login and identification. |
| | `agent_id` | `BIGINT` (nullable, unique) | **The Link.** A foreign key to the `agents` table. If not `NULL`, this user is a bot. |
| **`agents`** | `id` | `BIGINT` | Primary key for the agent profile. |
| | `ai_logic_path` | `VARCHAR` | The fully qualified class path to the AI strategy (e.g., `App\Agents\MinimaxAgent::class`). |
| | `available_hour_est` | `TINYINT` | The hour (0-23, EST) the agent can **start** a new game. |
| | `difficulty` | `TINYINT` | The agent's base skill level (1-10). |
| | `supported_game_titles` | `JSON` | An array of game title slugs (e.g., `["checkers", "hearts"]`) or the string `"all"`. |
| | `configuration` | `JSON` | Stores mode-specific overrides (e.g., `{"hearts": {"blitz_difficulty": 8}}`). |

---

## ⚙️ II. Agent Services & Logic

The agent system is managed by two distinct services and a formal contract, ensuring a clean separation of responsibilities.

### A. The `AgentContract` Interface

To ensure consistency across all AI implementations, every agent logic class **must** implement the `AgentContract`.

**`app/Interfaces/AgentContract.php`**
```php
namespace App\Interfaces;

use App\Models\Game;

interface AgentContract
{
    /**
     * Calculate the next best action for the agent.
     *
     * @param Game $game The current game model.
     * @param int $difficulty The difficulty level (1-10) for this specific game.
     * @return object The action DTO representing the agent's chosen move.
     */
    public function calculateNextAction(Game $game, int $difficulty): object;
}
```

### B. `AgentSchedulingService`

This service is solely responsible for finding an available agent for a game.

**`app/Services/Agents/AgentSchedulingService.php`**

| Method | Logic |
| :--- | :--- |
| **`findAvailableAgent(string $titleSlug): ?User`** | 1. **Get Current Hour (EST):** Determine the current hour (0-23) in the `America/New_York` timezone. <br> 2. **Query for Eligible Agents:** Find `User` models where `agent_id` is NOT NULL and join with the `agents` table to filter based on: <br>    - `available_hour_est` matches the current hour. <br>    - `supported_game_titles` contains the requested `$titleSlug` OR is `"all"`. <br> 3. **Filter for Busy Status:** From the eligible agents, filter out any who are currently in an `active` or `pending` game using the `isAgentBusy()` method. <br> 4. **Return:** Return the first available, non-busy `User` model, or `null`. |
| **`isAgentBusy(User $agentUser): bool`** | Checks if the agent's `user_id` is present in the `players` table for any game with a status of `active` or `pending`. This query remains unchanged. |

### C. `AgentService` & Asynchronous Moves

This service orchestrates an agent's turn, introducing human-like delays via a background job.

**`app/Services/Agents/AgentService.php`**
```php
namespace App\Services\Agents;

use App\Jobs\CalculateAgentMove;
use App\Models\Game;
use App\Models\User;

class AgentService
{
    /**
     * Dispatches a job to calculate and perform an agent's move.
     */
    public function performMove(Game $game, User $agentUser): void
    {
        CalculateAgentMove::dispatch($game, $agentUser);
    }
}
```

**`app/Jobs/CalculateAgentMove.php`**
This job runs in the background to prevent the game from freezing while the AI "thinks".

```php
namespace App\Jobs;

// ... use statements for Queueable, SerializesModels, etc.

class CalculateAgentMove implements ShouldQueue
{
    public function __construct(public Game $game, public User $agentUser) {}

    public function handle(): void
    {
        // 1. Get agent profile and determine difficulty
        $agentProfile = $this->agentUser->agent;
        $difficulty = $agentProfile->difficulty; // Use overrides from `configuration` if present

        // 2. Instantiate the logic class from the profile
        $strategy = app($agentProfile->ai_logic_path); // Use service container

        // 3. Calculate the move (this part is fast)
        $action = $strategy->calculateNextAction($this->game, $difficulty);

        // 4. Simulate "thinking" time
        $thinkingTime = random_int(1, 8);
        sleep($thinkingTime);

        // 5. Apply the action to the game state
        $this->game->applyAction($action, $this->agentUser->id);
        
        // 6. Advance the game to the next turn
        $this->game->advanceToNextTurn();
    }
}
```

---

## 🔗 III. System Integration

### A. Quickplay Matchmaking

The Quickplay system will be modified to prioritize human-vs-human matches.

```php
// Logic inside the Quickplay processing job...

// 1. Attempt to find a human match for the player in the queue.
$humanOpponent = findHumanOpponent($player);

if ($humanOpponent) {
    // Create game between two humans.
    return;
}

// 2. If no human is found after a delay, find an agent.
if ($player->waitedInQueueFor() > 15) {
    $agentOpponent = app(AgentSchedulingService::class)->findAvailableAgent($gameTitle);
    if ($agentOpponent) {
        // Create game between the human and the agent.
    }
}
```

### B. Game Loop: AI Turn Execution

When it is an AI's turn to move, the game engine will use the `AgentService` to dispatch the move calculation job.

```php
// Inside the game engine when a turn starts...

$currentPlayer = $game->getCurrentPlayer(); // Get the User model

if ($currentPlayer->isAgent()) {
    // The current player is an AI.
    // Dispatch the job to handle the turn in the background.
    app(AgentService::class)->performMove($game, $currentPlayer);
    
    // The game loop can now end for this turn. The job will handle the rest.
} else {
    // The player is human. Broadcast a "YourTurn" event to the client.
    broadcast(new YourTurnEvent($currentPlayer, $game));
}
```

This V2 architecture provides a robust, realistic, and highly configurable foundation for creating a vibrant ecosystem of both human and AI players.