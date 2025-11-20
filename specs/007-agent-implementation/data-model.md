# Data Model: Agent Implementation

This document defines the data structures required for the Agent Implementation feature, based on the entities identified in the feature specification.

## 1. `agents` Table

This table stores the core AI-specific profile and configuration for each agent.

| Column                | Data Type | Constraints / Details                                        |
| --------------------- | --------- | ------------------------------------------------------------ |
| `id`                  | `BIGINT`  | Primary Key                                                  |
| `ai_logic_path`       | `VARCHAR` | **Required**. The fully qualified class name of the AI strategy. Example: `App\Agents\Implementations\MinimaxAgent::class`. |
| `available_hour_est`  | `TINYINT` | **Required**. The hour (0-23) in the `America/New_York` timezone when the agent can start a new game. |
| `difficulty`          | `TINYINT` | **Required**. The agent's base skill level (1-10). Default: 5. |
| `supported_game_titles` | `JSON`    | **Required**. An array of game title slugs or the string `"all"`. Example: `["checkers", "hearts"]`. |
| `configuration`       | `JSON`    | **Nullable**. Stores mode-specific overrides. Example: `{"hearts": {"blitz_difficulty": 8}}`. |
| `created_at`          | `TIMESTAMP` | Managed by Eloquent.                                         |
| `updated_at`          | `TIMESTAMP` | Managed by Eloquent.                                         |

### Validation Rules

-   `ai_logic_path` must be a valid, existing class that implements `AgentContract`.
-   `available_hour_est` must be an integer between 0 and 23.
-   `difficulty` must be an integer between 1 and 10.

## 2. `users` Table (Existing)

The existing `users` table will be used to provide the primary identity for agents. The key is the `agent_id` foreign key.

| Column     | Data Type          | Constraints / Details                                        |
| ---------- | ------------------ | ------------------------------------------------------------ |
| `id`       | `BIGINT`           | Primary Key.                                                 |
| `agent_id` | `BIGINT` (nullable) | **Foreign Key** to `agents.id`. If this value is not `NULL`, the user is an AI agent. A `UNIQUE` constraint ensures a one-to-one relationship. |
| `name`     | `VARCHAR`          | For agents, this will be their public-facing persona name (e.g., "Agent Smith"). |
| `username` | `VARCHAR`          | A unique handle for the agent.                               |
| `email`    | `VARCHAR`          | A unique, fake email for the agent (e.g., `agent_1@protocol.game`). |
| `password` | `VARCHAR`          | Should be set to a long, random, unusable string for agents. |

## 3. Relationships

-   **User to Agent**: A `User` `hasOne` `Agent`. This relationship is optional (a user is not required to be an agent).
-   **Agent to User**: An `Agent` `belongsTo` a `User`. This relationship is required.

```php
// In App\Models\User.php
public function agent(): HasOne
{
    return $this->hasOne(Agent::class);
}

public function isAgent(): bool
{
    return $this->agent_id !== null;
}

// In App\Models\Agent.php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```
