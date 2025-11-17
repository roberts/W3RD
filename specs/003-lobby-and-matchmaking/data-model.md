# Data Model: Lobby and Matchmaking

**Purpose**: To define the database schema required for the persistent Lobby system.

## 1. `lobbies` Table

This table stores the core information for each created lobby.

| Column | Type | Constraints & Details |
| :--- | :--- | :--- |
| `id` | `bigint`, `unsigned` | Primary Key, Auto-increment |
| `ulid` | `char(26)` | Unique, Indexed. For public-facing identifiers. |
| `game_title` | `varchar(255)` | Indexed. Foreign key to a (conceptual) `game_titles` table. Stored as `GameTitle` enum value. |
| `host_id` | `bigint`, `unsigned` | Indexed. Foreign key to `users.id`. |
| `is_public` | `boolean` | Default: `false`. Indexed. `true` for discoverable lobbies. |
| `min_players` | `tinyint`, `unsigned` | Default: `2`. The minimum number of accepted players required to start. |
| `scheduled_at` | `timestamp`, `nullable` | The time the game is scheduled to start. `NULL` for immediate games. |
| `status` | `varchar(255)` | Default: `'pending'`. Indexed. Stored as `LobbyStatus` enum value (`pending`, `ready`, `cancelled`, `completed`). |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

### State Transitions (`status`)

*   `pending` -> `ready`: When the minimum number of players have accepted (for immediate games).
*   `pending` -> `cancelled`: If the host cancels the lobby or a scheduled game fails to meet start conditions.
*   `ready` -> `completed`: After the `Game` record has been successfully created.
*   `pending` -> `completed`: For a scheduled game that meets conditions at its scheduled time.

## 2. `lobby_players` Table

This pivot table links users to lobbies and tracks their individual status.

| Column | Type | Constraints & Details |
| :--- | :--- | :--- |
| `id` | `bigint`, `unsigned` | Primary Key, Auto-increment |
| `lobby_id` | `bigint`, `unsigned` | Indexed. Foreign key to `lobbies.id`. |
| `user_id` | `bigint`, `unsigned` | Indexed. Foreign key to `users.id`. |
| `status` | `varchar(255)` | Default: `'pending'`. Indexed. Stored as `LobbyPlayerStatus` enum value (`pending`, `accepted`, `declined`). |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

**Unique Constraint**: A composite unique key on (`lobby_id`, `user_id`) must be enforced to prevent a user from being in the same lobby more than once.

### State Transitions (`status`)

*   `pending` -> `accepted`: When an invited player accepts the invitation.
*   `pending` -> `declined`: When an invited player declines the invitation.

## 3. Enums

### `LobbyStatus.php`

```php
enum LobbyStatus: string
{
    case PENDING = 'pending';
    case READY = 'ready';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
}
```

### `LobbyPlayerStatus.php`

```php
enum LobbyPlayerStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case DECLINED = 'declined';
}
```
