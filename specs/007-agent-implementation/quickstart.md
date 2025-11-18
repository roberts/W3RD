# Quickstart: Agent Implementation

This document provides a high-level guide for developers to get started with the Agent Implementation feature.

## 1. Creating a New Agent

Creating a new agent involves two main steps: creating the AI logic and seeding the agent's profile in the database.

### Step 1: Create the AI Logic Class

All agent logic must implement the `AgentContract`.

1.  Create a new class in `app/Agents/Implementations/`.
2.  Implement the `calculateNextAction(Game $game, int $difficulty): object` method. This method should contain the core AI decision-making logic and return an Action DTO (e.g., `MoveAction.php`).

**Example: `app/Agents/Implementations/RandomMover.php`**
```php
namespace App\Agents\Implementations;

use App\Interfaces\AgentContract;
use App\Models\Game;

class RandomMover implements AgentContract
{
    public function calculateNextAction(Game $game, int $difficulty): object
    {
        $possibleMoves = $game->getPossibleMoves();
        $randomMove = $possibleMoves[array_rand($possibleMoves)];
        
        // Return the appropriate Action DTO for that action.
        return new \App\Games\Checkers\Actions\MoveAction($randomMove);
    }
}
```

### Step 2: Seed the Agent in the Database

Agents are created via database seeders or an admin panel. An `AgentFactory` will be created to simplify this process.

1.  **Create the `Agent` record**:
    -   `ai_logic_path`: `RandomMover::class`
    -   `difficulty`: `2`
    -   `supported_game_titles`: `["checkers"]`
    -   `available_hour_est`: `18` (6 PM EST)
2.  **Create the `User` record**:
    -   `name`: "Rookie Randy"
    -   `username`: "randy_the_rookie"
    -   `agent_id`: The ID of the newly created `Agent` record.
    -   Set a secure, random password.

## 2. How Agent Actions are Triggered

The system is entirely automated. Once an agent is in a game, no manual intervention is needed.

1.  **Game Turn Starts**: The game engine determines it is an agent's turn.
2.  **Service is Called**: The engine calls `app(AgentService::class)->performMove($game, $agentUser)`.
3.  **Job is Dispatched**: The `AgentService` dispatches the `CalculateAgentAction` job to the queue.
4.  **Job Executes**: The job runs in the background:
    -   It instantiates the correct AI logic class from the agent's `ai_logic_path`.
    -   It calls `calculateNextAction()`.
    -   It `sleeps` for 1-8 seconds to simulate thinking.
    -   It applies the action to the game, which broadcasts the update to all players.
    -   It advances the game to the next turn.

## 3. Key Files for this Feature

-   **Specification**: `specs/007-agent-implementation/spec.md`
-   **Data Model**: `specs/007-agent-implementation/data-model.md`
-   **Agent Contract**: `app/Interfaces/AgentContract.php`
-   **Agent Services**: `app/Services/Agents/`
-   **Agent Logic**: `app/Agents/Implementations/`
-   **Background Job**: `app/Jobs/CalculateAgentAction.php`
-   **Admin Controller**: `app/Http/Controllers/Admin/AgentController.php`
