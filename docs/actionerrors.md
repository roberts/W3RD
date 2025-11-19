# Action Error Responses

This document details all error responses that API clients will receive during gameplay when submitting actions that fail validation or violate game rules.

## Error Response Structure

All error responses follow a consistent JSON structure:

```json
{
  "message": "Human-readable error message",
  "error_code": "machine_readable_error_code",
  "game_title": "game-slug",
  "severity": "error|warning",
  "retryable": true|false,
  "context": {
    // Additional context specific to the error
  }
}
```

## HTTP Status Codes

- **400 Bad Request**: Structural issues with the request data (missing fields, wrong types, unknown action types)
- **422 Unprocessable Entity**: Valid request structure but violates game rules
- **429 Too Many Requests**: Rate limiting or cooldown violations
- **403 Forbidden**: Access denied (not a player in the game)
- **404 Not Found**: Game or resource not found
- **409 Conflict**: Concurrent action conflict

---

## Structural Validation Errors (HTTP 400)

These errors occur when the request data structure is invalid, before game rules are evaluated.

### Unknown Action Type

**Trigger**: Action type not supported by the game

```json
{
  "message": "Unknown action type: invalid_move",
  "error_code": "unknown_action_type",
  "game_title": "validate-four",
  "context": {
    "action_type": "invalid_move",
    "supported_types": ["drop_piece", "pop_out"]
  }
}
```

### Missing Required Field

**Trigger**: Required field not included in action data

```json
{
  "message": "Missing required field: column for drop_piece action",
  "error_code": "missing_required_field",
  "game_title": "validate-four",
  "context": {
    "action_type": "drop_piece",
    "missing_field": "column",
    "required_fields": ["column"]
  }
}
```

### Invalid Field Type

**Trigger**: Field value is wrong type (e.g., string instead of integer)

```json
{
  "message": "Field \"column\" must be an integer, string provided",
  "error_code": "invalid_field_type",
  "game_title": "checkers",
  "context": {
    "field": "column",
    "expected_type": "integer",
    "actual_type": "string",
    "actual_value": "3"
  }
}
```

---

## Game Rule Violations (HTTP 422)

These errors occur when the action structure is valid but violates game-specific rules.

### Base Game Errors

These error codes apply across all games:

#### Not Player Turn

**Trigger**: Attempting to play when it's another player's turn

```json
{
  "message": "It is not your turn.",
  "error_code": "not_player_turn",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "current_turn": {
      "player_ulid": "01JMQXXX",
      "player_username": "alice",
      "player_position": 1
    },
    "your_info": {
      "player_ulid": "01JMQYYY",
      "player_username": "bob",
      "player_position": 2
    },
    "turn_number": 15
  }
}
```

#### Game Already Completed

**Trigger**: Attempting to play in a finished game

```json
{
  "message": "This game is not active.",
  "error_code": "game_already_completed",
  "game_title": "validate-four",
  "severity": "error",
  "context": {
    "game_status": "completed",
    "finished_at": "2025-01-15T14:30:00Z",
    "winner": {
      "player_ulid": "01JMQXXX",
      "player_username": "alice",
      "player_position": 1
    }
  }
}
```

**For Abandoned Games**:

```json
{
  "message": "This game is not active.",
  "error_code": "game_already_completed",
  "game_title": "checkers",
  "severity": "error",
  "context": {
    "game_status": "abandoned",
    "finished_at": "2025-01-15T14:30:00Z",
    "reason": "Game was abandoned by players"
  }
}
```

#### Invalid Position

**Trigger**: Position coordinates outside valid range

```json
{
  "message": "Invalid board position.",
  "error_code": "invalid_position",
  "game_title": "checkers",
  "severity": "error",
  "context": {
    "position": {"row": 8, "column": 3},
    "board_size": {"rows": 8, "columns": 8}
  }
}
```

#### Position Occupied

**Trigger**: Attempting to place piece where one already exists

```json
{
  "message": "Position is already occupied.",
  "error_code": "position_occupied",
  "game_title": "validate-four",
  "severity": "error",
  "context": {
    "position": {"row": 5, "column": 3},
    "occupied_by": "player_2"
  }
}
```

#### No Piece at Position

**Trigger**: Attempting to move/interact with empty position

```json
{
  "message": "No piece at the specified position.",
  "error_code": "no_piece_at_position",
  "game_title": "checkers",
  "severity": "error",
  "context": {
    "position": {"row": 3, "column": 4}
  }
}
```

#### Wrong Player Piece

**Trigger**: Attempting to move opponent's piece

```json
{
  "message": "You can only move your own pieces.",
  "error_code": "wrong_player_piece",
  "game_title": "checkers",
  "severity": "error",
  "context": {
    "position": {"row": 5, "column": 2},
    "piece_owner": "player_1",
    "your_position": 2
  }
}
```

#### Invalid Move Pattern

**Trigger**: Move doesn't follow piece movement rules

```json
{
  "message": "Invalid move pattern for this piece.",
  "error_code": "invalid_move_pattern",
  "game_title": "checkers",
  "severity": "error",
  "context": {
    "from": {"row": 3, "column": 2},
    "to": {"row": 4, "column": 5},
    "piece_type": "pawn",
    "allowed_patterns": ["diagonal_forward"]
  }
}
```

#### Move Blocked

**Trigger**: Path to destination is blocked

```json
{
  "message": "Move is blocked by another piece.",
  "error_code": "move_blocked",
  "game_title": "checkers",
  "severity": "error",
  "context": {
    "from": {"row": 2, "column": 1},
    "to": {"row": 4, "column": 3},
    "blocking_position": {"row": 3, "column": 2}
  }
}
```

#### Invalid Game Phase

**Trigger**: Action not allowed in current game phase

```json
{
  "message": "This action is not valid in the current game phase.",
  "error_code": "invalid_game_phase",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "current_phase": "playing",
    "required_phase": "passing",
    "action_type": "pass_cards"
  }
}
```

#### Invalid Action Data

**Trigger**: Action data doesn't match requirements

```json
{
  "message": "Invalid action data provided.",
  "error_code": "invalid_action_data",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "issue": "Must pass exactly 3 cards",
    "provided": 2,
    "required": 3
  }
}
```

---

## Validate Four (Connect 4) Errors

### Drop Piece Errors

#### Column Full

**Error Code**: `column_full`

```json
{
  "message": "Column is full, cannot drop piece.",
  "error_code": "column_full",
  "game_title": "validate-four",
  "severity": "error",
  "context": {
    "column": 3,
    "filled_rows": 6
  }
}
```

#### Invalid Column

**Error Code**: `invalid_position`

```json
{
  "message": "Invalid board position.",
  "error_code": "invalid_position",
  "game_title": "validate-four",
  "severity": "error",
  "context": {
    "column": 8,
    "valid_range": "0-6"
  }
}
```

### Pop Out Mode Errors

#### No Piece at Bottom

**Error Code**: `no_piece_at_bottom`

```json
{
  "message": "No piece at the bottom of this column.",
  "error_code": "no_piece_at_bottom",
  "game_title": "validate-four",
  "severity": "error",
  "context": {
    "column": 2,
    "bottom_row": 0
  }
}
```

#### Not Your Piece

**Error Code**: `not_your_piece`

```json
{
  "message": "You can only pop out your own pieces.",
  "error_code": "not_your_piece",
  "game_title": "validate-four",
  "severity": "error",
  "context": {
    "column": 4,
    "piece_owner": "player_1",
    "your_position": 2
  }
}
```

#### Invalid Pop State

**Error Code**: `invalid_pop_state`

```json
{
  "message": "Pop out action not allowed in current game state.",
  "error_code": "invalid_pop_state",
  "game_title": "validate-four",
  "severity": "error",
  "context": {
    "reason": "Must drop at least 4 pieces before using pop"
  }
}
```

---

## Checkers Errors

### Move Piece Errors

#### Invalid Diagonal Move

**Error Code**: `invalid_move_pattern`

```json
{
  "message": "Invalid move pattern for this piece.",
  "error_code": "invalid_move_pattern",
  "game_title": "checkers",
  "severity": "error",
  "context": {
    "from": {"row": 3, "column": 2},
    "to": {"row": 4, "column": 4},
    "piece_type": "regular",
    "issue": "Regular pieces move one diagonal space"
  }
}
```

#### Backward Move Not Allowed

**Error Code**: `invalid_move_pattern`

```json
{
  "message": "Invalid move pattern for this piece.",
  "error_code": "invalid_move_pattern",
  "game_title": "checkers",
  "severity": "error",
  "context": {
    "from": {"row": 4, "column": 3},
    "to": {"row": 3, "column": 2},
    "piece_type": "regular",
    "issue": "Regular pieces cannot move backward (kings only)"
  }
}
```

### Jump Piece Errors

#### No Piece to Capture

**Error Code**: `no_piece_at_position`

```json
{
  "message": "No piece at the specified position.",
  "error_code": "no_piece_at_position",
  "game_title": "checkers",
  "severity": "error",
  "context": {
    "position": {"row": 4, "column": 3},
    "action": "capture"
  }
}
```

#### Cannot Capture Own Piece

**Error Code**: `wrong_player_piece`

```json
{
  "message": "Cannot capture your own piece.",
  "error_code": "wrong_player_piece",
  "game_title": "checkers",
  "severity": "error",
  "context": {
    "capture_position": {"row": 4, "column": 3},
    "piece_owner": 1,
    "your_position": 1
  }
}
```

#### Jump Destination Occupied

**Error Code**: `position_occupied`

```json
{
  "message": "Position is already occupied.",
  "error_code": "position_occupied",
  "game_title": "checkers",
  "severity": "error",
  "context": {
    "position": {"row": 5, "column": 4},
    "occupied_by": "player_2"
  }
}
```

#### Must Complete Jump Chain

**Error Code**: `invalid_action_data`

```json
{
  "message": "Must continue jumping when additional jumps are available.",
  "error_code": "invalid_action_data",
  "game_title": "checkers",
  "severity": "error",
  "context": {
    "position": {"row": 5, "column": 4},
    "available_jumps": [
      {"to": {"row": 7, "column": 6}, "captures": {"row": 6, "column": 5}}
    ]
  }
}
```

---

## Hearts Errors

### Pass Cards Errors

#### Wrong Card Count

**Error Code**: `invalid_action_data`

```json
{
  "message": "Invalid action data provided.",
  "error_code": "invalid_action_data",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "issue": "Must pass exactly 3 cards",
    "provided": 2,
    "required": 3
  }
}
```

#### Card Not in Hand

**Error Code**: `invalid_action_data`

```json
{
  "message": "Invalid action data provided.",
  "error_code": "invalid_action_data",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "issue": "Card not in your hand",
    "card": "AS",
    "your_hand": ["2C", "3C", "4C", "5C", "..."]
  }
}
```

#### Wrong Phase for Passing

**Error Code**: `invalid_game_phase`

```json
{
  "message": "This action is not valid in the current game phase.",
  "error_code": "invalid_game_phase",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "current_phase": "playing",
    "required_phase": "passing",
    "action_type": "pass_cards"
  }
}
```

### Play Card Errors

#### Card Not in Hand

**Error Code**: `invalid_action_data`

```json
{
  "message": "Invalid action data provided.",
  "error_code": "invalid_action_data",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "issue": "Card not in your hand",
    "card": "QS",
    "your_hand": ["2C", "3C", "4C", "5C", "..."]
  }
}
```

#### Must Follow Suit

**Error Code**: `invalid_action_data`

```json
{
  "message": "Invalid action data provided.",
  "error_code": "invalid_action_data",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "issue": "Must follow suit when able",
    "card_played": "5D",
    "suit_required": "clubs",
    "cards_in_suit": ["2C", "3C", "4C"]
  }
}
```

#### Hearts Not Broken

**Error Code**: `invalid_action_data`

```json
{
  "message": "Invalid action data provided.",
  "error_code": "invalid_action_data",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "issue": "Hearts not broken yet",
    "card_played": "5H",
    "hearts_broken": false,
    "alternative_cards": ["2C", "3D", "4S"]
  }
}
```

#### Cannot Lead Hearts on First Trick

**Error Code**: `invalid_action_data`

```json
{
  "message": "Invalid action data provided.",
  "error_code": "invalid_action_data",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "issue": "Cannot play hearts or Queen of Spades on first trick",
    "card_played": "AH",
    "trick_number": 1
  }
}
```

#### Must Play Two of Clubs First

**Error Code**: `invalid_action_data`

```json
{
  "message": "Invalid action data provided.",
  "error_code": "invalid_action_data",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "issue": "Must lead with 2 of Clubs on first trick",
    "card_played": "3C",
    "required_card": "2C"
  }
}
```

### Phase-Specific Errors

#### Waiting for Other Players

**Error Code**: `invalid_game_phase`

```json
{
  "message": "This action is not valid in the current game phase.",
  "error_code": "invalid_game_phase",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "current_phase": "waiting_for_passes",
    "required_phase": "playing",
    "waiting_for": ["player_2", "player_3"]
  }
}
```

#### Round Complete

**Error Code**: `invalid_game_phase`

```json
{
  "message": "This action is not valid in the current game phase.",
  "error_code": "invalid_game_phase",
  "game_title": "hearts",
  "severity": "error",
  "context": {
    "current_phase": "round_complete",
    "required_phase": "playing",
    "scores": {
      "player_1": 26,
      "player_2": 0,
      "player_3": 0,
      "player_4": 0
    }
  }
}
```

---

## Access Control Errors (HTTP 403)

### Not a Player

**Trigger**: Authenticated user is not a player in this game

```json
{
  "message": "You are not a player in this game",
  "error_code": "access_denied",
  "game_ulid": "01JMQXXX",
  "context": {
    "user_id": 123
  }
}
```

---

## Rate Limiting Errors (HTTP 429)

### Rate Limit Exceeded

**Trigger**: Too many requests in time window

```json
{
  "message": "Rate limit exceeded. Please wait before trying again.",
  "error_code": "rate_limit_exceeded",
  "severity": "warning",
  "retryable": true,
  "context": {
    "limit": 60,
    "window": "60 seconds",
    "retry_after": "45 seconds"
  }
}
```

### Cooldown Active

**Trigger**: Action on cooldown after recent use

```json
{
  "message": "Action is on cooldown.",
  "error_code": "cooldown_active",
  "severity": "warning",
  "retryable": true,
  "context": {
    "action": "pop_out",
    "cooldown_remaining": "30 seconds",
    "retry_after": 30
  }
}
```

---

## Concurrent Action Errors (HTTP 409)

### Concurrent Action Conflict

**Trigger**: Another action processed simultaneously

```json
{
  "message": "Your action could not be processed due to a concurrent action.",
  "error_code": "concurrent_action_conflict",
  "severity": "warning",
  "retryable": true,
  "context": {
    "conflict_type": "game_state_changed",
    "current_turn": 16,
    "your_submitted_turn": 15
  }
}
```

---

## Best Practices for API Clients

### Error Handling Strategy

1. **Check HTTP Status Code First**
   - 400: Fix request structure before retrying
   - 422: Show game rule violation to user
   - 429: Implement exponential backoff
   - 403/404: Handle as unrecoverable
   - 409: Refresh game state and allow retry

2. **Use Error Codes for Logic**
   - Don't parse error messages (they may change)
   - Use `error_code` field for programmatic decisions
   - Store error code mappings in your client

3. **Display Context to Users**
   - Use `context` object to show specific details
   - Show turn information when not player's turn
   - Display current player username/position
   - Show required fields for structural errors

4. **Handle Retryable Errors**
   - Check `retryable` field
   - Use `retry_after` value for timing
   - Don't retry non-retryable errors

5. **Severity Handling**
   - `error`: Block action, show error to user
   - `warning`: May show as temporary notification

### Example Client Implementation

```javascript
async function submitAction(gameUlid, actionType, actionData) {
  try {
    const response = await fetch(`/api/v1/games/${gameUlid}/actions`, {
      method: 'POST',
      body: JSON.stringify({ action_type: actionType, ...actionData })
    });
    
    if (!response.ok) {
      const error = await response.json();
      
      switch (response.status) {
        case 400:
          // Structural error - show developer error or fix client
          console.error('Invalid request structure:', error);
          showError(`Invalid request: ${error.context.missing_field || error.message}`);
          break;
          
        case 422:
          // Game rule violation - show to user
          handleGameRuleError(error);
          break;
          
        case 429:
          // Rate limited - wait and retry
          if (error.retryable) {
            await sleep(error.context.retry_after * 1000);
            return submitAction(gameUlid, actionType, actionData);
          }
          break;
          
        case 403:
          // Access denied - redirect or show error
          showError('You do not have access to this game');
          break;
          
        case 409:
          // Conflict - refresh game state and allow retry
          await refreshGameState(gameUlid);
          showError('Game state changed. Please try again.');
          break;
          
        default:
          showError('An unexpected error occurred');
      }
      
      return null;
    }
    
    return await response.json();
  } catch (err) {
    console.error('Network error:', err);
    showError('Connection error. Please check your network.');
    return null;
  }
}

function handleGameRuleError(error) {
  switch (error.error_code) {
    case 'not_player_turn':
      showTurnIndicator(error.context.current_turn);
      showMessage(`It's ${error.context.current_turn.player_username}'s turn`);
      break;
      
    case 'game_already_completed':
      if (error.context.winner) {
        showGameOver(error.context.winner);
      } else {
        showMessage('This game has ended');
      }
      break;
      
    case 'column_full':
      highlightColumn(error.context.column, 'error');
      showMessage(`Column ${error.context.column + 1} is full`);
      break;
      
    case 'invalid_move_pattern':
      showMessage(error.context.issue || error.message);
      break;
      
    default:
      showMessage(error.message);
  }
}
```

---

## Error Code Reference

### Quick Lookup Table

| Error Code | HTTP Status | Retryable | Common Causes |
|------------|-------------|-----------|---------------|
| `unknown_action_type` | 400 | No | Typo in action type, outdated client |
| `missing_required_field` | 400 | No | Missing data in request |
| `invalid_field_type` | 400 | No | Wrong data type (string vs int) |
| `not_player_turn` | 422 | No | Turn order violation |
| `game_already_completed` | 422 | No | Game finished |
| `invalid_position` | 422 | No | Out of bounds coordinates |
| `position_occupied` | 422 | No | Space already taken |
| `no_piece_at_position` | 422 | No | Empty space |
| `wrong_player_piece` | 422 | No | Trying to move opponent's piece |
| `invalid_move_pattern` | 422 | No | Illegal move for piece type |
| `move_blocked` | 422 | No | Path obstructed |
| `invalid_game_phase` | 422 | No | Wrong phase for action |
| `invalid_action_data` | 422 | No | Data doesn't meet requirements |
| `column_full` | 422 | No | Connect 4 column full |
| `no_piece_at_bottom` | 422 | No | PopOut mode - nothing to pop |
| `not_your_piece` | 422 | No | PopOut mode - wrong owner |
| `invalid_pop_state` | 422 | No | PopOut not allowed yet |
| `rate_limit_exceeded` | 429 | Yes | Too many requests |
| `cooldown_active` | 429 | Yes | Action on cooldown |
| `access_denied` | 403 | No | Not a player in game |
| `concurrent_action_conflict` | 409 | Yes | Simultaneous actions |

---

## Version History

- **2025-11-19**: Initial documentation with comprehensive error codes for ValidateFour, Checkers, and Hearts
