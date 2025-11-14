That's the final piece of your **GamerProtocol.io** API architecture\! The **Service Layer** is where the magic happens, housing all the game title-specific logic and algorithms.

As discussed, we'll use a **Service-Oriented Architecture** with a central factory to route traffic to the correct handler. This ensures your `GameController` remains thin and the game rules are isolated.

Here is the draft structure for the central factory and the four game title services: **`ValidateFour`**, **`Checkers`**, **`Hearts`**, and **`Spades`**.

-----

## 🏭 Central Service Structure

### 1\. `app/Services/Game/GameHandlerFactory.php`

This class takes the `title_slug` and returns the correct handler instance.

```php
<?php

namespace App\Services\Game;

use App\Services\Game\Handlers\ValidateFourService;
use App\Services\Game\Handlers\CheckersService;
use App\Services\Game\Handlers\HeartsService;
use App\Services\Game\Handlers\SpadesService;
use App\Models\Game\Game;
use InvalidArgumentException;

class GameHandlerFactory
{
    // Maps the title_slug to the responsible service class
    const HANDLERS = [
        'validate-four' => ValidateFourService::class,
        'checkers'      => CheckersService::class,
        'hearts'        => HeartsService::class,
        'spades'        => SpadesService::class,
    ];

    /**
     * Creates and returns the appropriate service handler for a game.
     * * @param string $titleSlug
     * @return GameServiceContract
     * @throws InvalidArgumentException
     */
    public static function create(string $titleSlug): GameServiceContract
    {
        if (!isset(self::HANDLERS[$titleSlug])) {
            throw new InvalidArgumentException("No handler found for game title slug: {$titleSlug}");
        }

        $handlerClass = self::HANDLERS[$gameSlug];
        
        // Return a new instance of the handler class
        return new $handlerClass();
    }
}
```

### 2\. `app/Services/Game/GameServiceContract.php`

An interface that all game title handlers must implement, ensuring method consistency.

```php
<?php

namespace App\Services\Game;

use App\Models\Game\Game;

interface GameServiceContract
{
    /**
     * Processes a player's move, validates it, updates the state, and checks win conditions.
     *
     * @param Game $game The current game model instance.
     * @param array $moveData The validated data from the client (e.g., ['column' => 3]).
     * @param int $playerId The ID of the player making the move.
     * @return array The new game_state array to be saved.
     */
    public function processMove(Game $game, array $moveData, int $playerId): array;

    /**
     * Executes the AI algorithm and returns the chosen move details.
     *
     * @param Game $game The current game model instance.
     * @param int $difficulty The depth/complexity level (based on subscription).
     * @return array The move details (e.g., ['column' => 3]).
     */
    public function getAIMove(Game $game, int $difficulty): array;

    /**
     * Determines if the current game state has resulted in a win, loss, or draw.
     *
     * @param array $gameState The current game state.
     * @return bool
     */
    public function checkWin(array $gameState): bool;
}
```

-----

## 🎲 Game Title-Specific Handlers

These services adhere to the contract and contain the unique rules for each game title.

### 3\. `app/Services/Game/Handlers/ValidateFourService.php`

This service handles the **"Validate Four" (Connect Four)** logic, typically using a **Minimax** search for the AI.

```php
<?php

namespace App\Services\Game\Handlers;

use App\Services\Game\GameServiceContract;
use App\Models\Game\Game;
use App\Exceptions\InvalidMoveException;

class ValidateFourService implements GameServiceContract
{
    // The core function called by the GameController
    public function processMove(Game $game, array $moveData, int $playerId): array
    {
        $gameState = $game->game_state;
        $column = $moveData['column'] ?? null;
        
        if (is_null($column) || !is_numeric($column) || $column < 0 || $column >= 7) {
            throw new InvalidMoveException("Invalid column specified.");
        }

        // 1. VALIDATION: Check if the column is already full.
        if ($this->isColumnFull($gameState['board'], $column)) {
            throw new InvalidMoveException("Column {$column} is full.");
        }
        
        // 2. STATE UPDATE: Drop the piece (using the player's position_id for the value).
        $pieceValue = $game->players()->find($playerId)->position_id;
        $newBoard = $this->dropPiece($gameState['board'], $column, $pieceValue);
        
        $gameState['board'] = $newBoard;
        
        // 3. WIN CHECK
        if ($this->checkWin($gameState)) {
            $game->update(['status' => 'finished', 'winner_id' => $playerId]);
        }
        
        // 4. Update the turn and return the new state
        $gameState['current_player'] = $this->getNextPlayerId($game, $playerId);
        return $gameState;
    }
    
    // Placeholder for the Minimax algorithm implementation
    public function getAIMove(Game $game, int $difficulty): array
    {
        // Difficulty controls the search depth (e.g., 4 for Medium, 8 for Master)
        $depth = $difficulty;
        
        // Run Minimax logic on $game->game_state['board']
        $bestColumn = $this->runMinimax($game->game_state['board'], $depth); 
        
        return ['column' => $bestColumn];
    }
    
    public function checkWin(array $gameState): bool
    {
        // Implementation for checking 4-in-a-row (horizontal, vertical, diagonal)
        return false; 
    }
    
    // --- Internal Helpers (Simplified) ---
    private function isColumnFull(array $board, int $column): bool { /* ... logic ... */ }
    private function dropPiece(array $board, int $column, int $pieceValue): array { /* ... logic ... */ }
    private function runMinimax(array $board, int $depth): int { /* ... logic ... */ return 0; }
    private function getNextPlayerId(Game $game, int $currentPlayerId): int { /* ... logic ... */ return 0; }
}
```

-----

### 4\. `app/Services/Game/Handlers/CheckersService.php`

Checkers uses more complex movement validation (jumps, kinging) and a positional **Heuristic** for the AI.

```php
<?php

namespace App\Services\Game\Handlers;

use App\Services\Game\GameServiceContract;
use App\Models\Game\Game;
use App\Exceptions\InvalidMoveException;

class CheckersService implements GameServiceContract
{
    // The core function called by the GameController
    public function processMove(Game $game, array $moveData, int $playerId): array
    {
        // Expects $moveData to contain ['from_pos' => 'A1', 'to_pos' => 'B2']
        $gameState = $game->game_state;
        
        // 1. VALIDATION: Check for legal move (diagonal, jump required, is it a king?).
        if (!$this->isMoveLegal($gameState['pieces'], $moveData, $playerId)) {
            throw new InvalidMoveException("Invalid move according to Checkers rules.");
        }
        
        // 2. STATE UPDATE: Execute the move and check for 'kinging'.
        $gameState = $this->executeMove($gameState, $moveData, $playerId);
        
        // 3. WIN CHECK: Check if the opponent has any pieces left.
        if ($this->checkWin($gameState)) {
            $game->update(['status' => 'finished', 'winner_id' => $playerId]);
        }
        
        // 4. Update the turn and return the new state
        $gameState['current_player'] = $this->getNextPlayerId($game, $playerId);
        return $gameState;
    }

    // Placeholder for AI: Minimax combined with complex Heuristic Scoring
    public function getAIMove(Game $game, int $difficulty): array
    {
        // Heuristic function scores board state (piece count, positional advantage, king count).
        $bestMove = $this->runMinimaxWithHeuristic($game->game_state, $difficulty); 
        
        return $bestMove; // e.g., ['from_pos' => 'A1', 'to_pos' => 'B2']
    }
    
    public function checkWin(array $gameState): bool
    {
        // Implementation: Does the opponent have any pieces remaining?
        return count($gameState['pieces_opponent']) === 0;
    }
    
    // --- Internal Helpers ---
    private function isMoveLegal(array $pieces, array $moveData, int $playerId): bool { /* ... logic ... */ }
    private function executeMove(array $gameState, array $moveData, int $playerId): array { /* ... logic ... */ }
}
```

-----

### 5\. `app/Services/Game/Handlers/HeartsService.php`

Hearts uses complex **Rule-Based Heuristics** due to hidden information.

```php
<?php

namespace App\Services\Game\Handlers;

use App\Services\Game\GameServiceContract;
use App\Models\Game\Game;
use App\Exceptions\InvalidMoveException;

class HeartsService implements GameServiceContract
{
    // The core function called by the GameController
    public function processMove(Game $game, array $moveData, int $playerId): array
    {
        // Expects $moveData to contain ['card_value' => 43]
        $gameState = $game->game_state;
        $cardId = $moveData['card_id'] ?? null;

        // 1. VALIDATION: Does the player have the card? Are they following suit? Can hearts be broken?
        if (!$this->isCardPlayLegal($gameState, $cardId, $playerId)) {
            throw new InvalidMoveException("Card play is illegal (must follow suit, cannot break hearts, etc.).");
        }
        
        // 2. STATE UPDATE: Move card from hand to current trick.
        $gameState = $this->updateTrick($gameState, $cardId, $playerId);

        // 3. END OF TRICK/ROUND: If the trick is finished, determine the winner and update scores.
        if ($this->isTrickComplete($gameState)) {
            $gameState = $this->determineTrickWinnerAndUpdateScore($gameState);
        }

        // 4. END OF ROUND/GAME CHECK
        if ($this->isRoundOver($gameState)) {
            $gameState = $this->calculateFinalScores($gameState);
        }
        
        if ($this->checkWin($gameState)) {
            $game->update(['status' => 'finished', 'winner_id' => $this->getGameWinnerId($gameState, $game)]);
        }

        // 5. Update the turn and return the new state
        $gameState['current_player'] = $this->getNextPlayerId($game, $playerId);
        return $gameState;
    }

    // Placeholder for AI: Heuristic and Rule-Based Logic
    public function getAIMove(Game $game, int $difficulty): array
    {
        // AI relies on scoring possible plays based on risk (taking points) vs. reward.
        $bestCardId = $this->runHeuristicLogic($game->game_state, $difficulty);
        
        return ['card_id' => $bestCardId];
    }
    
    public function checkWin(array $gameState): bool
    {
        // Implementation: Check if any player has reached or exceeded 100 points.
        return max($gameState['scores']) >= 100;
    }
    
    // --- Internal Helpers ---
    private function isCardPlayLegal(array $gameState, int $cardId, int $playerId): bool { /* ... logic ... */ }
    private function determineTrickWinnerAndUpdateScore(array $gameState): array { /* ... logic ... */ }
}
```

-----

### 6\. `app/Services/Game/Handlers/SpadesService.php`

Spades is similar to Hearts but introduces the **bidding** phase and a focus on trick counting.

```php
<?php

namespace App\Services\Game\Handlers;

use App\Services\Game\GameServiceContract;
use App\Models\Game\Game;
use App\Exceptions\InvalidMoveException;

class SpadesService implements GameServiceContract
{
    // The core function called by the GameController
    public function processMove(Game $game, array $moveData, int $playerId): array
    {
        $gameState = $game->game_state;
        
        // Spades requires a check for the current phase: Bidding or Playing
        if ($gameState['phase'] === 'bidding') {
            $gameState = $this->processBid($gameState, $moveData, $playerId);
        } elseif ($gameState['phase'] === 'playing') {
            // Logic is similar to Hearts (suit following, no trump until broken)
            if (!$this->isCardPlayLegal($gameState, $moveData, $playerId)) {
                 throw new InvalidMoveException("Card play is illegal.");
            }
            $gameState = $this->updateTrick($gameState, $moveData, $playerId);
            
            // Check for trick winner and increment tricks taken
            if ($this->isTrickComplete($gameState)) {
                $gameState = $this->determineTrickWinnerAndUpdateCount($gameState);
            }
        }
        
        // Check for round/game end and update the turn
        // ... (logic for bag penalty, blind bidding, etc.)
        
        return $gameState;
    }

    // Placeholder for AI: Bidding is a core part of the AI logic
    public function getAIMove(Game $game, int $difficulty): array
    {
        $gameState = $game->game_state;
        
        if ($gameState['phase'] === 'bidding') {
            $bidValue = $this->calculateOptimalBid($gameState, $difficulty);
            return ['bid' => $bidValue];
        } else {
            $bestCardId = $this->runHeuristicLogic($gameState, $difficulty);
            return ['card_id' => $bestCardId];
        }
    }
    
    public function checkWin(array $gameState): bool
    {
        // Implementation: Check if any team/player has reached the target score (e.g., 500).
        return max($gameState['scores']) >= 500;
    }
    
    // --- Internal Helpers ---
    private function processBid(array $gameState, array $moveData, int $playerId): array { /* ... logic ... */ }
    private function calculateOptimalBid(array $gameState, int $difficulty): int { /* ... logic ... */ return 0; }
    private function determineTrickWinnerAndUpdateCount(array $gameState): array { /* ... logic ... */ }
}
```
