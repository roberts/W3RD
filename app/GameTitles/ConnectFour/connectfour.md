# Connect Four API Documentation

## Game Description
Connect Four is a two-player connection board game, in which the players choose a color and then take turns dropping colored discs from the top into a seven-column, six-row vertically suspended grid. The pieces fall straight down, occupying the lowest available space within the column. The objective of the game is to be the first to form a horizontal, vertical, or diagonal line of four of one's own discs.

## Rules
1. **Players**: 2 players (Red vs Yellow/Black).
2. **Turn-based**: Players alternate turns.
3. **Objective**: Connect 4 discs of your color in a row (horizontally, vertically, or diagonally).
4. **Draw**: If the board fills up before either player achieves four in a row, the game is a draw.

---

## Game Modes

### Standard Mode
- **Grid**: 7 columns x 6 rows.
- **Win Condition**: Connect 4.
- **Timer**: Standard turn timer (default 30s).
- **Actions**: `drop_piece`.

### PopOut Mode
- **Grid**: 7 columns x 6 rows.
- **Win Condition**: Connect 4.
- **Special Rule**: Players can "pop" their own discs out from the bottom row, causing all discs above to shift down.
- **Actions**: `drop_piece`, `pop_out`.

### Five Mode
- **Grid**: Standard dimensions.
- **Win Condition**: Connect 5 discs instead of 4.
- **Actions**: `drop_piece`.

### EightBySeven Mode
- **Grid**: 8 columns x 7 rows.
- **Win Condition**: Connect 4.
- **Actions**: `drop_piece`.

### NineBySix Mode
- **Grid**: 9 columns x 6 rows.
- **Win Condition**: Connect 4.
- **Actions**: `drop_piece`.

---

## API Endpoints

### 1. Submit Game Action
**Endpoint**: `POST /api/v1/games/{game_id}/action`
**Headers**:
- `Authorization`: `Bearer {token}`
- `Content-Type`: `application/json`
- `Accept`: `application/json`

#### Request Body
The payload depends on the `action_type`.

**Action: Drop Piece**
Used in all modes.
```json
{
    "action_type": "drop_piece",
    "action_details": {
        "column": 3
    }
}
```
- `column`: Integer (0-based index). For a 7-column board, valid values are 0-6.

**Action: Pop Out**
Used only in **PopOut Mode**.
```json
{
    "action_type": "pop_out",
    "action_details": {
        "column": 3
    }
}
```
- `column`: Integer (0-based index). You must have one of your own pieces at the bottom of this column to pop it out.

#### Response (Success - 200 OK)
Returns the updated game state and action confirmation.
```json
{
    "data": {
        "success": true,
        "game": {
            "id": "01H...",
            "status": "active",
            "turn_number": 5,
            "game_state": {
                // See Game State JSON below
            }
        },
        "action_record": {
            "ulid": "01H...",
            "type": "drop_piece",
            "details": { "column": 3 },
            "timestamp": "2025-11-24T12:00:00Z"
        },
        "context": {
            "next_player_ulid": "01H...",
            "timeout": {
                "timelimit_seconds": 30,
                "grace_period_seconds": 2,
                "penalty": "forfeit"
            }
        }
    },
    "message": "Action applied successfully"
}
```

---

### 2. Get Game Details
**Endpoint**: `GET /api/v1/games/{game_id}`
**Headers**: `Authorization: Bearer {token}`

#### Response (200 OK)
```json
{
    "data": {
        "id": "01H...",
        "title_slug": "connect-four",
        "mode": "standard",
        "status": "active",
        "game_state": {
            "board": [
                [null, null, null, null, null, null, null], // Row 0 (Top)
                [null, null, null, null, null, null, null],
                [null, null, null, null, null, null, null],
                [null, null, null, null, null, null, null],
                [null, null, null, null, null, null, null],
                [null, null, "01H_PLAYER1", "01H_PLAYER2", null, null, null] // Row 5 (Bottom)
            ],
            "columns": 7,
            "rows": 6,
            "connectCount": 4,
            "isDraw": false,
            "currentPlayerUlid": "01H_PLAYER1_ULID",
            "winnerUlid": null,
            "phase": "playing",
            "status": "active"
        },
        "players": [
            {
                "ulid": "01H_PLAYER1_ULID",
                "username": "PlayerOne",
                "avatar": "..."
            },
            {
                "ulid": "01H_PLAYER2_ULID",
                "username": "PlayerTwo",
                "avatar": "..."
            }
        ]
    }
}
```

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
            "title": "Connect Four",
            "status": "active",
            "current_turn_player_ulid": "01H...",
            "created_at": "..."
        }
        // ...
    ],
        "meta": {
        "current_page": 1,
        "last_page": 5
    }
}

---

### 4. Get Available Actions
**Endpoint**: `GET /api/v1/games/{game_id}/options`
**Headers**: `Authorization: Bearer {token}`

#### Response (200 OK)
Returns the list of valid actions the current player can take.

```json
{
    "data": {
        "options": [
            {
                "type": "drop_piece",
                "details": { "column": 0 }
            },
            {
                "type": "drop_piece",
                "details": { "column": 1 }
            }
            // ...
        ],
        "is_your_turn": true,
        "phase": "playing",
        "deadline": "2025-11-24T12:00:30Z",
        "timelimit_seconds": 30
    }
}
```

---

## Game State JSON Specification
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
| `board` | `Array<Array<string\|null>>` | 2D array representing the grid. `board[row][col]`. `null` is empty, string is Player ULID. Row 0 is TOP. |
| `columns` | `int` | Number of columns (e.g., 7). |
| `rows` | `int` | Number of rows (e.g., 6). |
| `connectCount` | `int` | Number of discs needed to win (e.g., 4). |
| `isDraw` | `boolean` | True if the game ended in a draw. |

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
        [null, null, null, null, null, null, null],
        [null, null, null, null, null, null, null],
        [null, null, null, null, null, null, null],
        [null, null, null, null, null, null, null],
        [null, null, null, null, null, null, null],
        [null, null, "01H_P1...", "01H_P2...", null, null, null]
    ],
    "columns": 7,
    "rows": 6,
    "connectCount": 4,
    "isDraw": false
}
```
