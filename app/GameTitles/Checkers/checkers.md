# Checkers API Documentation

## Game Description
Checkers (also known as Draughts) is a strategy board game for two players which involves diagonal moves of uniform game pieces and mandatory captures by jumping over opponent pieces. This implementation follows standard US/UK rules (8x8 board).

## Rules
1. **Players**: 2 players (Black vs Red/White).
2. **Movement**: Pieces move diagonally forward one square.
3. **Capturing**: If an opponent's piece is diagonally adjacent and the square beyond is empty, you *must* jump and capture it.
4. **Kings**: When a piece reaches the furthest row, it becomes a King and can move/capture backwards.
5. **Multi-jumps**: If a jump lands you in a position to make another jump, you must continue jumping.
6. **Win Condition**: Capture all opponent pieces or block them so they have no legal moves.

---

## Game Modes

### Standard Mode
- **Grid**: 8x8 Board.
- **Pieces**: 12 pieces per player.
- **Forced Jumps**: Enabled (Mandatory).
- **Timer**: Standard turn timer.

---

## API Endpoints

### 1. Submit Game Action
**Endpoint**: `POST /api/v1/games/{game_id}/action`
**Headers**:
- `Authorization`: `Bearer {token}`
- `Content-Type`: `application/json`

#### Request Body
The payload depends on the `action_type`.

**Action: Move Piece**
Standard non-capturing move.
```json
{
    "action_type": "move_piece",
    "action_details": {
        "from_row": 5,
        "from_col": 2,
        "to_row": 4,
        "to_col": 3
    }
}
```

**Action: Jump Piece**
Single capture.
```json
{
    "action_type": "jump_piece",
    "action_details": {
        "from_row": 5,
        "from_col": 2,
        "to_row": 3,
        "to_col": 4,
        "captured_row": 4,
        "captured_col": 3
    }
}
```

**Action: Double Jump**
Multi-capture sequence.
```json
{
    "action_type": "double_jump_piece",
    "action_details": {
        "from_row": 7, "from_col": 0,
        "mid_row": 5, "mid_col": 2,
        "to_row": 3, "to_col": 4,
        "captured_row_1": 6, "captured_col_1": 1,
        "captured_row_2": 4, "captured_col_2": 3
    }
}
```

**Action: Triple Jump**
Extended multi-capture.
```json
{
    "action_type": "triple_jump_piece",
    "action_details": {
        "from_row": 7, "from_col": 0,
        "mid1_row": 5, "mid1_col": 2,
        "mid2_row": 3, "mid2_col": 4,
        "to_row": 1, "to_col": 6,
        "captured_row_1": 6, "captured_col_1": 1,
        "captured_row_2": 4, "captured_col_2": 3,
        "captured_row_3": 2, "captured_col_3": 5
    }
}
```

#### Response (Success - 200 OK)
```json
{
    "data": {
        "success": true,
        "game": {
            "id": "01H...",
            "game_state": {
                // See Game State JSON below
            }
        },
        "action_record": {
            "type": "move_piece",
            "details": { ... }
        }
    }
}
```

---

### 2. Get Game Details
**Endpoint**: `GET /api/v1/games/{game_id}`

#### Response (200 OK)
```json
{
    "data": {
        "id": "01H...",
        "title_slug": "checkers",
        "game_state": {
            "board": [
                [null, {"player": "P2_ULID", "king": false}, null, ...], // Row 0
                [{"player": "P2_ULID", "king": false}, null, ...],
                // ...
                [null, null, null, null, null, null, null, null],
                // ...
                [null, {"player": "P1_ULID", "king": true}, null, ...] // Row 7
            ],
            "currentPlayerUlid": "P1_ULID",
            "winnerUlid": null,
            "isDraw": false,
            "phase": "playing",
            "status": "active"
        }
    }
}
```

---

        }
    }
}

---

### 3. List Games
**Endpoint**: `GET /api/v1/games`
**Query Params**: `?status=active&per_page=20`

#### Response (200 OK)
```json
{
    "data": [
        {
            "id": "01H...",
            "title": "Checkers",
            "status": "active",
            "current_turn_player_ulid": "01H...",
            "created_at": "..."
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5
    }
}
```

---

### 4. Get Available Actions
**Endpoint**: `GET /api/v1/games/{game_id}/options`
**Headers**: `Authorization: Bearer {token}`

#### Response (200 OK)
Returns the list of valid actions. For Checkers, this is crucial for knowing mandatory jumps.

```json
{
    "data": {
        "options": [
            {
                "type": "jump_piece",
                "details": {
                    "from_row": 5, "from_col": 2,
                    "to_row": 3, "to_col": 4,
                    "captured_row": 4, "captured_col": 3
                }
            }
        ],
        "is_your_turn": true,
        "phase": "playing",
        "deadline": "2025-11-24T12:00:30Z",
        "timelimit_seconds": 60
    }
}
```

---

## Game State JSON Specification
The `game_state` object is the source of truth for the frontend renderer. It represents the current state of the game board, players, and progression.

### API Endpoint
**Endpoint**: `GET /api/v1/games/{game_id}`
The game state is returned in the `data.game_state` field of the response.

### Shared Fields
| Field | Type | Description |
|-------|------|-------------|
| `players` | `Map<string, Object>` | Map of Player ULID to Player State Object. |
| `currentPlayerUlid` | `string\|null` | ULID of the player whose turn it is. |
| `winnerUlid` | `string\|null` | ULID of the winner, or null if game is ongoing/draw. |
| `phase` | `string` | Current game phase (e.g., "playing"). |
| `status` | `string` | "active", "completed", "abandoned". |

### Game-Specific Fields
| Field | Type | Description |
|-------|------|-------------|
| `board` | `Array<Array<Object\|null>>` | 8x8 Grid. Row 0 is Top (Player 2 home), Row 7 is Bottom (Player 1 home). |
| `board[r][c]` | `Object` | `{ "player": "ulid", "king": boolean }` or `null`. |
| `isDraw` | `boolean` | True if draw declared. |

### Example JSON
```json
{
    "players": {
        "01H_P1...": { "ulid": "01H_P1...", "username": "PlayerOne" },
        "01H_P2...": { "ulid": "01H_P2...", "username": "PlayerTwo" }
    },
    "currentPlayerUlid": "01H_P1...",
    "winnerUlid": null,
    "phase": "playing",
    "status": "active",
    "board": [
        [null, {"player": "01H_P2...", "king": false}, null, ...],
        [{"player": "01H_P2...", "king": false}, null, ...],
        ...
        [null, {"player": "01H_P1...", "king": true}, null, ...]
    ],
    "isDraw": false
}
```
