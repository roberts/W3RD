## 1\. Polymorphic Logic Contract

The core structure remains the same but emphasizes the generic nature of the contract.

### A. Agent Strategy Contract (PHP)

This contract defines the local algorithm implementations.

**`app/Services/Game/Agents/AgentStrategyContract.php`**

```php
<?php

namespace App\Services\Game\Agents;

interface AgentStrategyContract
{
    /** Calculates the next move based purely on the current game state. */
    public function calculateMove(array $gameState, int $playerValue): array;
}
```

### B. External Agent Contract (API)

The contract for logic involving external network calls.

**`app/Services/Game/Agents/ExternalAgentContract.php`**

```php
<?php

namespace App\Services\Game\Agents;

interface ExternalAgentContract
{
    /** Calls a third-party API to get the next move. */
    public function requestMove(array $gameState, string $matchId): array;
}
```

-----

## 2\. 🤖 Agent Implementations (Minimax & Heuristic)

These classes are named to reflect their function as **strategies** for the **Agent** resource.

| Service | Purpose | Logic Used |
| :--- | :--- | :--- |
| `app/Services/Game/Agents/ValidateFourMinimax.php` | Calculates the optimal move for Connect Four. | **Minimax Search** (implements `AgentStrategyContract`) |
| `app/Services/Game/Agents/CheckersHeuristic.php` | Calculates moves for Checkers. | **Heuristic Search** (implements `AgentStrategyContract`) |

-----

## 3\. 🌐 External Agent Adapter

This adapter correctly uses the term `Agent` and handles the external network communication.

**`app/Services/Game/Agents/ExternalAgentAdapter.php`**

```php
<?php

namespace App\Services\Game\Agents;

use GuzzleHttp\Client; // Requires guzzlehttp/guzzle
// implements ExternalAgentContract...

class ExternalAgentAdapter implements ExternalAgentContract
{
    protected Client $client;

    public function __construct()
    {
        // ... Guzzle client initialization using configs ...
    }

    public function requestMove(array $gameState, string $matchId): array
    {
        // ... API formatting, Guzzle call, and response parsing logic ...
        
        return $this->parseResponse($response);
    }
}
```

-----

## 4\. 🔗 The Unified Game Service Router

The main game service is the router, selecting the correct **Agent Strategy** based on the database configuration.

**`app/Services/Game/Handlers/ValidateFourService.php`**

```php
// ... within the ValidateFourService class ...

public function getAIMove(Match $match): array
{
    // ... logic to retrieve the Agent model instance ...
    $agent = $agentPlayer->playable; 

    // Case A: Internal Agent Strategy
    if ($agent->agent_type === 'ai' && $agent->ai_logic_path) {
        // Correctly loads the internal strategy class name (e.g., ValidateFourMinimax)
        $strategyClass = $agent->ai_logic_path; 
        $strategy = new $strategyClass(); 
        
        // Executes the internal PHP algorithm
        return $strategy->calculateMove($match->game_state, $agentPlayer->position_id);
    }
    
    // Case B: External Agent Strategy
    if ($agent->agent_type === 'external_api') {
        // Uses the external adapter
        $adapter = new ExternalAgentAdapter();
        
        // Executes the Guzzle HTTP call
        return $adapter->requestMove($match->game_state, $match->ulid);
    }

    throw new \Exception("Agent type not supported.");
}
```

-----

## 🤖 Agent Logic Implementations

This structure outlines the core files needed for your deterministic opponent logic, adhering to the **Agent Strategy Pattern** and the **Service-Oriented Architecture** within your Laravel application.

The files contain the complex algorithms but remain decoupled from the Laravel `Request` and `Match` model, making them easy to test.

### 1\. `app/Services/Game/Agents/ValidateFourMinimax.php`

This service implements the logic for **Validate Four (Connect Four)**, which uses the **Minimax algorithm with Alpha-Beta Pruning** to efficiently find the optimal column.

```php
<?php

declare(strict_types=1);

namespace App\Services\Game\Agents;

use App\Services\Game\Agents\AgentStrategyContract;

final class ValidateFourMinimax implements AgentStrategyContract
{
    private const ROW_COUNT = 6;
    private const COLUMN_COUNT = 7;
    private const WINNING_SCORE = 100000000000000; // Arbitrarily high value

    /**
     * Calculates the best column for the current player using Minimax.
     *
     * @param array $gameState The array representation of the 6x7 board.
     * @param int $playerValue The value representing the AI player (e.g., 1 or 2).
     * @return array The move details (e.g., ['column' => 3]).
     */
    public function calculateMove(array $gameState, int $playerValue): array
    {
        // Difficulty level (depth) is typically injected or set by the calling service.
        // For this example, we assume a default depth based on the Agent tier.
        $depth = 5; 
        
        $board = $gameState['board'] ?? []; // The 6x7 grid array
        
        // The main minimax function returns the best score and the best column.
        [$score, $column] = $this->minimax($board, $depth, -INF, INF, true, $playerValue);
        
        return ['column' => $column];
    }

    /**
     * The core Minimax implementation with Alpha-Beta Pruning.
     * * @param array $board The current board state.
     * @param int $depth The remaining search depth.
     * @param float $alpha The alpha value (maximizer's best score found so far).
     * @param float $beta The beta value (minimizer's best score found so far).
     * @param bool $maximizingPlayer True if it's the maximizer's turn (AI).
     * @param int $aiPlayerValue The piece value of the AI.
     * @return array [score, column]
     */
    private function minimax(array $board, int $depth, float $alpha, float $beta, bool $maximizingPlayer, int $aiPlayerValue): array
    {
        $opponentValue = ($aiPlayerValue === 1) ? 2 : 1;
        $validLocations = $this->getValidLocations($board);
        
        // Base case: Check for terminal nodes (win, draw, or max depth reached)
        if ($depth === 0 || $this->isWinningMove($board, $aiPlayerValue) || $this->isWinningMove($board, $opponentValue) || empty($validLocations)) {
            if ($this->isWinningMove($board, $aiPlayerValue)) {
                return [self::WINNING_SCORE * $depth, -1];
            }
            if ($this->isWinningMove($board, $opponentValue)) {
                return [-self::WINNING_SCORE * $depth, -1];
            }
            return [0, -1]; // Draw or depth cut-off
        }

        if ($maximizingPlayer) {
            $value = -INF;
            $column = $validLocations[array_rand($validLocations)]; // Default move
            
            foreach ($validLocations as $col) {
                // Simulate move
                $tempBoard = $this->simulateMove($board, $col, $aiPlayerValue);
                
                // Recursive call (switching to minimizing player)
                $score = $this->minimax($tempBoard, $depth - 1, $alpha, $beta, false, $aiPlayerValue)[0];
                
                if ($score > $value) {
                    $value = $score;
                    $column = $col;
                }
                $alpha = max($alpha, $value);
                
                // Alpha-Beta Pruning
                if ($alpha >= $beta) {
                    break;
                }
            }
            return [$value, $column];

        } else { // Minimizing player (Opponent/Human)
            $value = INF;
            $column = $validLocations[array_rand($validLocations)];
            
            foreach ($validLocations as $col) {
                $tempBoard = $this->simulateMove($board, $col, $opponentValue);
                
                // Recursive call (switching to maximizing player)
                $score = $this->minimax($tempBoard, $depth - 1, $alpha, $beta, true, $aiPlayerValue)[0];
                
                if ($score < $value) {
                    $value = $score;
                    $column = $col;
                }
                $beta = min($beta, $value);
                
                // Alpha-Beta Pruning
                if ($alpha >= $beta) {
                    break;
                }
            }
            return [$value, $column];
        }
    }

    // --- Board Utility Functions (Must be implemented) ---
    private function getValidLocations(array $board): array { /* ... logic ... */ }
    private function simulateMove(array $board, int $col, int $piece): array { /* ... logic ... */ }
    private function isWinningMove(array $board, int $piece): bool { /* ... logic ... */ }
    // Note: A full implementation would also include a static 'evaluateWindow' heuristic
    // for scoring non-terminal nodes, which is crucial for depth cutoffs.
}
```

-----

### 2\. `app/Services/Game/Agents/CheckersHeuristic.php`

This service handles **Checkers**, relying on a complex **Heuristic Evaluation Function** to score board positions, as the game tree is too large for deep searching.

```php
<?php

declare(strict_types=1);

namespace App\Services\Game\Agents;

use App\Services\Game\Agents\AgentStrategyContract;

final class CheckersHeuristic implements AgentStrategyContract
{
    private const MAX_DEPTH = 5; // Search depth for Checkers is shallower

    /**
     * Calculates the best move using a Heuristic-weighted Minimax search.
     *
     * @param array $gameState The array representation of the board pieces/positions.
     * @param int $playerValue The value representing the AI player.
     * @return array The move details (e.g., ['from_pos' => 'A1', 'to_pos' => 'B2']).
     */
    public function calculateMove(array $gameState, int $playerValue): array
    {
        // This method would call a Minimax function, but instead of checking for
        // a perfect win/loss at depth 0, it calls the static heuristic function.
        
        $bestMove = $this->runMinimaxHeuristic($gameState, self::MAX_DEPTH, $playerValue);
        
        return $bestMove;
    }

    /**
     * Statically evaluates a board position by calculating a score.
     * This score guides the Minimax algorithm when it hits the search depth limit.
     *
     * @param array $boardState The current board state array.
     * @param int $pieceValue The piece value of the player being evaluated.
     * @return int The calculated score.
     */
    private function evaluateBoard(array $boardState, int $pieceValue): int
    {
        $score = 0;
        $opponentValue = ($pieceValue === 1) ? 2 : 1;
        
        // --- 1. Material Balance (Dominant Feature) ---
        // Kings are worth significantly more than regular pieces.
        $myPieces = $this->countPieces($boardState, $pieceValue);
        $opponentPieces = $this->countPieces($boardState, $opponentValue);
        
        $myKings = $this->countKings($boardState, $pieceValue);
        $opponentKings = $this->countKings($boardState, $opponentValue);
        
        // Weighted Sum (The weight of King is typically 2-3x a normal piece)
        $score += ($myPieces * 100) - ($opponentPieces * 100);
        $score += ($myKings * 150) - ($opponentKings * 150);
        
        // --- 2. Positional Advantage (Mid-Game Feature) ---
        // Encourage moving pieces toward the center and the opponent's back rank.
        $score += $this->calculateCenterControl($boardState, $pieceValue) * 10;
        
        // --- 3. Safety/Defense (Mid-Game Feature) ---
        // Penalize exposed pieces; reward protected pieces.
        $score -= $this->countExposedPieces($boardState, $pieceValue) * 5;
        
        // --- 4. Kinging Potential (End-Game Feature) ---
        // Reward pieces that are close to the opponent's back rank.
        $score += $this->calculatePromotionDistance($boardState, $pieceValue) * 8;
        
        return $score;
    }
    
    // --- Minimax & Utility Functions (Similar to ValidateFourMinimax, but uses evaluateBoard) ---
    private function runMinimaxHeuristic(array $gameState, int $depth, int $playerValue): array { /* ... logic ... */ return []; }
    private function countPieces(array $boardState, int $pieceValue): int { /* ... logic ... */ return 0; }
    private function countKings(array $boardState, int $pieceValue): int { /* ... logic ... */ return 0; }
    private function calculateCenterControl(array $boardState, int $pieceValue): int { /* ... logic ... */ return 0; }
    private function countExposedPieces(array $boardState, int $pieceValue): int { /* ... logic ... */ return 0; }
    private function calculatePromotionDistance(array $boardState, int $pieceValue): int { /* ... logic ... */ return 0; }
}
```