# Data Model: Connect Four

This document defines the data structures for the `game_state` and `action_details` JSON fields for the "Connect Four" game.

## 1. `game_state` JSON Structure

The `game_state` will be managed by the `ConnectFourGameState` object. It will contain the following fields:

```json
{
  "board": [
    [0, 0, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 0],
    [1, 0, 0, 0, 0, 0],
    [2, 1, 0, 0, 0, 0],
    [1, 2, 1, 0, 0, 0],
    [2, 1, 2, 2, 0, 0],
    [1, 2, 1, 1, 0, 0]
  ],
  "board_width": 7,
  "board_height": 6,
  "connect_length": 4,
  "current_player_ulid": "01HGETP3J4G3J4G3J4G3J4G3J4",
  "player_ulids": ["01HGETP3J4G3J4G3J4G3J4G3J4", "01HGETP3J5G5J5G5J5G5J5G5"],
  "player_map": {
    "1": "01HGETP3J4G3J4G3J4G3J4G3J4",
    "2": "01HGETP3J5G5J5G5J5G5J5G5"
  },
  "winner_ulid": null,
  "is_draw": false
}
```

- **`board`**: A 2D array representing the game grid. The outer array represents columns, the inner array represents rows from bottom (index 0) to top. `0` = empty, `1` = player 1, `2` = player 2.
- **`board_width`**: Integer defining the number of columns.
- **`board_height`**: Integer defining the number of rows.
- **`connect_length`**: Integer defining the number of discs needed to win (e.g., 4 or 5).
- **`current_player_ulid`**: The ULID of the player whose turn it is.
- **`player_ulids`**: An array of player ULIDs participating in the game.
- **`player_map`**: Maps the board integers (1, 2) to the actual player `ulid`.
- **`winner_ulid`**: `null` until a player wins.
- **`is_draw`**: `false` until the game ends in a draw.

## 2. `action_details` JSON Structure

The `action_details` will be represented by specific Action DTOs.

### Action Type: `drop_disc`

- **Description**: A player drops their disc into a column.
- **DTO**: `DropDisc.php`
- **JSON Payload**:
  ```json
  {
    "column": 3
  }
  ```
- **`column`**: The zero-based index of the column to drop the disc into.

### Action Type: `pop_out`

- **Description**: A player removes their own disc from the bottom of a column. (Only available in "Pop Out" mode).
- **DTO**: `PopOut.php`
- **JSON Payload**:
  ```json
  {
    "column": 4
  }
  ```
- **`column`**: The zero-based index of the column to pop a disc from.
