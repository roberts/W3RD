# Hearts API Documentation

## Game Description
Hearts is a trick-taking playing card game for four players. The game is also known as Black Lady, The Dirty, Dark Lady, Slippery Anne, Chase the Lady, Crubs, Black Queen and Black Maria.

## Rules
1. **Players**: 4 players.
2. **Objective**: Avoid winning tricks containing Hearts or the Queen of Spades.
3. **Scoring**:
   - Each Heart = 1 penalty point.
   - Queen of Spades = 13 penalty points.
   - **Shooting the Moon**: If you take ALL Hearts and the Queen of Spades, you get 0 points and everyone else gets 26.
4. **Game End**: The game ends when a player reaches 100 points. The player with the lowest score wins.

---

## Game Modes

### Standard Mode
- **Players**: 4.
- **Passing**:
  - Round 1: Pass Left.
  - Round 2: Pass Right.
  - Round 3: Pass Across.
  - Round 4: Hold (No pass).
  - Repeat.
- **First Trick**: Player with 2 of Clubs leads.
- **Breaking Hearts**: Hearts cannot be led until a Heart has been played (broken) or the player has only Hearts.

---

## API Endpoints

### 1. Submit Game Action
**Endpoint**: `POST /api/v1/games/{game_id}/action`

#### Request Body

**Action: Pass Cards**
Used during the `passing` phase.
```json
{
    "action_type": "pass_cards",
    "action_details": {
        "cards": ["2H", "QS", "KD"]
    }
}
```
- `cards`: Array of 3 card codes (e.g., "2H" = 2 of Hearts, "QS" = Queen of Spades, "TC" = 10 of Clubs).

**Action: Play Card**
Used during the `trick_in_progress` phase.
```json
{
    "action_type": "play_card",
    "action_details": {
        "card": "2C"
    }
}
```
- `card`: The card code to play.

**Action: Claim Remaining Tricks**
Used when a player's hand is high enough to win all remaining tricks.
```json
{
    "action_type": "claim_remaining_tricks",
    "action_details": {}
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
        }
    }
}
```

---

### 2. Get Game Details
**Endpoint**: `GET /api/v1/games/{game_id}`

#### Response (200 OK)
**Note**: The `hands` field is redacted for other players. You only see your own hand.

```json
{
    "data": {
        "id": "01H...",
        "title_slug": "hearts",
        "game_state": {
            "players": {
                "P1_ULID": { "score": 0, "tricks_taken": 0 },
                "P2_ULID": { "score": 15, "tricks_taken": 1 }
            },
            "hands": {
                "P1_ULID": ["2C", "3H", "KS", ...], // Your hand
                "P2_ULID": ["??", "??", "??", ...], // Opponent hand (redacted)
                "P3_ULID": ["??", "??", "??", ...],
                "P4_ULID": ["??", "??", "??", ...]
            },
            "currentTrick": {
                "P1_ULID": "2C",
                "P2_ULID": "KC"
            },
            "trickLeaderUlid": "P1_ULID",
            "heartsBroken": false,
            "roundNumber": 1,
            "phase": "trick_in_progress", // or "passing", "dealing"
            "currentPlayerUlid": "P3_ULID"
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
            "title": "Hearts",
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
Returns the list of valid actions.

```json
{
    "data": {
        "options": [
            {
                "type": "play_card",
                "details": { "card": "2C" }
            },
            {
                "type": "play_card",
                "details": { "card": "KS" }
            }
        ],
        "is_your_turn": true,
        "phase": "trick_in_progress",
        "deadline": "2025-11-24T12:00:30Z",
        "timelimit_seconds": 45
    }
}
```

---

## Game State JSON Specification
The `game_state` object is the source of truth for the frontend renderer. It represents the current state of the game table, hands, and scores.

### API Endpoint
**Endpoint**: `GET /api/v1/games/{game_id}`
The game state is returned in the `data.game_state` field of the response.

### Shared Fields
| Field | Type | Description |
|-------|------|-------------|
| `players` | `Map<string, Object>` | Map of Player ULID to Player State Object (score, tricks taken). |
| `currentPlayerUlid` | `string\|null` | ULID of the player whose turn it is. |
| `winnerUlid` | `string\|null` | ULID of the winner, or null if game is ongoing. |
| `phase` | `string` | `dealing`, `passing`, `trick_in_progress`, `round_complete`, `game_over`. |
| `status` | `string` | "active", "completed", "abandoned". |

### Game-Specific Fields
| Field | Type | Description |
|-------|------|-------------|
| `hands` | `Map<string, Array<string>>` | Cards held by each player. Opponent cards are redacted as `??`. |
| `currentTrick` | `Map<string, string>` | Cards played in the current trick. Key is Player ULID, Value is Card Code. |
| `trickLeaderUlid` | `string` | Who started the current trick. |
| `heartsBroken` | `boolean` | Whether hearts have been played yet. |
| `roundNumber` | `int` | Current round (1-based). |

### Card Codes
- **Format**: `{Rank}{Suit}`
- **Ranks**: `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `T` (10), `J`, `Q`, `K`, `A`.
- **Suits**: `H` (Hearts), `D` (Diamonds), `C` (Clubs), `S` (Spades).
- **Examples**: `2C` (Two of Clubs), `TH` (Ten of Hearts), `AS` (Ace of Spades).

### Example JSON
```json
{
    "players": {
        "01H_P1...": { "score": 0, "tricks_taken": 0 },
        "01H_P2...": { "score": 15, "tricks_taken": 1 },
        "01H_P3...": { "score": 5, "tricks_taken": 0 },
        "01H_P4...": { "score": 26, "tricks_taken": 3 }
    },
    "currentPlayerUlid": "01H_P3...",
    "winnerUlid": null,
    "phase": "trick_in_progress",
    "status": "active",
    "hands": {
        "01H_P1...": ["2C", "3H", "KS"],
        "01H_P2...": ["??", "??", "??"],
        "01H_P3...": ["??", "??", "??"],
        "01H_P4...": ["??", "??", "??"]
    },
    "currentTrick": {
        "01H_P1...": "2C",
        "01H_P2...": "KC"
    },
    "trickLeaderUlid": "01H_P1...",
    "heartsBroken": false,
    "roundNumber": 1
}
```
