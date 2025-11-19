<?php

declare(strict_types=1);

namespace App\Games\Checkers;

use App\Games\BaseBoardGameTitle;
use App\Games\GameOutcome;
use App\Games\ValidationResult;
use App\Interfaces\GameTitleContract;
use App\Models\Game\Action;
use App\Models\Game\Game;
use Carbon\Carbon;

/**
 * Base Checkers game implementation.
 *
 * Implements standard American Checkers (English Draughts) rules.
 */
abstract class BaseCheckers extends BaseBoardGameTitle implements GameTitleContract
{
    /**
     * Default turn time limit in seconds.
     */
    protected const DEFAULT_TURN_TIME_SECONDS = 60;

    /**
     * Grace period in seconds to account for network latency.
     */
    protected const NETWORK_GRACE_PERIOD_SECONDS = 2;

    /**
     * Default penalty when a turn times out.
     */
    protected const DEFAULT_TIMEOUT_PENALTY = 'forfeit';

    /**
     * Create initial game state for a new Checkers game.
     *
     * Checkers requires exactly 2 players.
     *
     * @param  string  ...$playerUlids  Player ULIDs (must be exactly 2)
     * @return GameState
     *
     * @throws \InvalidArgumentException If not exactly 2 players provided
     */
    public function createInitialState(string ...$playerUlids): object
    {
        if (count($playerUlids) !== 2) {
            throw new \InvalidArgumentException('Checkers requires exactly 2 players');
        }

        return GameState::createNew(
            playerOneUlid: $playerUlids[0],
            playerTwoUlid: $playerUlids[1],
        );
    }

    /**
     * Get the state class name.
     */
    public function getStateClass(): string
    {
        return GameState::class;
    }

    /**
     * Returns the fully qualified class name of the game state object.
     */
    protected function getGameStateClass(): string
    {
        return GameState::class;
    }

    /**
     * Get the action factory class name.
     */
    public function getActionFactory(): string
    {
        return Actions\ActionFactory::class;
    }

    /**
     * Get the structured rules for Checkers.
     */
    public static function getRules(): array
    {
        return [
            'title' => 'Checkers (American/English Draughts)',
            'description' => 'Capture all of your opponent\'s pieces or block them from making any legal moves.',
            'sections' => [
                [
                    'title' => 'Setup',
                    'content' => <<<'MARKDOWN'
                    *   8x8 board with alternating light and dark squares.
                    *   Each player starts with 12 pieces placed on the dark squares of the three rows closest to them.
                    *   Red pieces start at the bottom, black pieces at the top.
                    MARKDOWN,
                ],
                [
                    'title' => 'Movement',
                    'content' => <<<'MARKDOWN'
                    *   Players take turns moving one piece per turn.
                    *   Regular pieces move diagonally forward one square to an empty dark square.
                    *   Kings (promoted pieces) can move diagonally forward or backward one square.
                    MARKDOWN,
                ],
                [
                    'title' => 'Captures',
                    'content' => <<<'MARKDOWN'
                    *   Captures are mandatory. If a capture is available, it must be taken.
                    *   Capture by jumping over an opponent's piece to an empty square beyond it.
                    *   Multiple captures can be made in a single turn if available after the first jump.
                    *   Captured pieces are removed from the board.
                    MARKDOWN,
                ],
                [
                    'title' => 'King Promotion',
                    'content' => <<<'MARKDOWN'
                    *   When a piece reaches the opposite end of the board, it is promoted to a King.
                    *   Kings can move and capture both forward and backward.
                    MARKDOWN,
                ],
                [
                    'title' => 'Winning',
                    'content' => <<<'MARKDOWN'
                    *   Win by capturing all of your opponent's pieces.
                    *   Win if your opponent has no legal moves available.
                    MARKDOWN,
                ],
            ],
        ];
    }

    /**
     * Validate a player's action.
     */
    public function validateAction(object $gameState, object $action): ValidationResult
    {
        if (! ($gameState instanceof GameState)) {
            return ValidationResult::invalid(
                'INVALID_STATE_TYPE',
                'Game state must be a GameState instance'
            );
        }

        // Action validation will be implemented with the Standard mode
        return ValidationResult::valid();
    }

    /**
     * Apply a valid action to the game state.
     */
    public function applyAction(object $gameState, object $action): object
    {
        if (! ($gameState instanceof GameState)) {
            return $gameState;
        }

        // Handle different action types
        if ($action instanceof Actions\MovePiece) {
            return $this->applyMovePiece($gameState, $action);
        }

        if ($action instanceof Actions\JumpPiece) {
            return $this->applyJumpPiece($gameState, $action);
        }

        if ($action instanceof Actions\DoubleJumpPiece) {
            return $this->applyDoubleJumpPiece($gameState, $action);
        }

        if ($action instanceof Actions\TripleJumpPiece) {
            return $this->applyTripleJumpPiece($gameState, $action);
        }

        return $gameState;
    }

    /**
     * Apply a move piece action.
     */
    protected function applyMovePiece(GameState $gameState, Actions\MovePiece $action): GameState
    {
        $newState = $gameState->withMovedPiece(
            $action->fromRow,
            $action->fromCol,
            $action->toRow,
            $action->toCol
        );

        // Check if piece should be promoted to king (reached opposite end)
        $piece = $newState->getPieceAt($action->toRow, $action->toCol);
        if ($piece !== null) {
            $playerUlids = array_keys($newState->players);
            $shouldPromote = ($piece['player'] === $playerUlids[0] && $action->toRow === 7) ||
                           ($piece['player'] === $playerUlids[1] && $action->toRow === 0);

            if ($shouldPromote && ! $piece['king']) {
                // Promote to king by moving again (which preserves everything but sets king)
                $board = $newState->board;
                $board[$action->toRow][$action->toCol]['king'] = true;
                $newState = new GameState(
                    players: $newState->players,
                    currentPlayerUlid: $this->getNextPlayerUlid($newState),
                    winnerUlid: $newState->winnerUlid,
                    phase: $newState->phase,
                    status: $newState->status,
                    board: $board,
                    isDraw: $newState->isDraw,
                );
            } else {
                $newState = new GameState(
                    players: $newState->players,
                    currentPlayerUlid: $this->getNextPlayerUlid($newState),
                    winnerUlid: $newState->winnerUlid,
                    phase: $newState->phase,
                    status: $newState->status,
                    board: $newState->board,
                    isDraw: $newState->isDraw,
                );
            }
        }

        return $newState;
    }

    /**
     * Apply a jump piece action.
     */
    protected function applyJumpPiece(GameState $gameState, Actions\JumpPiece $action): GameState
    {
        // Remove the captured piece first
        $newState = $gameState->withRemovedPiece($action->capturedRow, $action->capturedCol);

        // Move the jumping piece
        $newState = $newState->withMovedPiece(
            $action->fromRow,
            $action->fromCol,
            $action->toRow,
            $action->toCol
        );

        // Check for king promotion
        $piece = $newState->getPieceAt($action->toRow, $action->toCol);
        if ($piece !== null) {
            $playerUlids = array_keys($newState->players);
            $shouldPromote = ($piece['player'] === $playerUlids[0] && $action->toRow === 7) ||
                           ($piece['player'] === $playerUlids[1] && $action->toRow === 0);

            if ($shouldPromote && ! $piece['king']) {
                $board = $newState->board;
                $board[$action->toRow][$action->toCol]['king'] = true;
                $newState = new GameState(
                    players: $newState->players,
                    currentPlayerUlid: $this->getNextPlayerUlid($newState),
                    winnerUlid: $newState->winnerUlid,
                    phase: $newState->phase,
                    status: $newState->status,
                    board: $board,
                    isDraw: $newState->isDraw,
                );
            } else {
                $newState = new GameState(
                    players: $newState->players,
                    currentPlayerUlid: $this->getNextPlayerUlid($newState),
                    winnerUlid: $newState->winnerUlid,
                    phase: $newState->phase,
                    status: $newState->status,
                    board: $newState->board,
                    isDraw: $newState->isDraw,
                );
            }
        }

        return $newState;
    }

    /**
     * Apply a double jump piece action.
     */
    protected function applyDoubleJumpPiece(GameState $gameState, Actions\DoubleJumpPiece $action): GameState
    {
        // Remove both captured pieces
        $newState = $gameState
            ->withRemovedPiece($action->capturedRow1, $action->capturedCol1)
            ->withRemovedPiece($action->capturedRow2, $action->capturedCol2);

        // Move through mid point to final position
        $newState = $newState->withMovedPiece(
            $action->fromRow,
            $action->fromCol,
            $action->toRow,
            $action->toCol
        );

        // Check for king promotion
        $piece = $newState->getPieceAt($action->toRow, $action->toCol);
        if ($piece !== null) {
            $playerUlids = array_keys($newState->players);
            $shouldPromote = ($piece['player'] === $playerUlids[0] && $action->toRow === 7) ||
                           ($piece['player'] === $playerUlids[1] && $action->toRow === 0);

            if ($shouldPromote && ! $piece['king']) {
                $board = $newState->board;
                $board[$action->toRow][$action->toCol]['king'] = true;
                $newState = new GameState(
                    players: $newState->players,
                    currentPlayerUlid: $this->getNextPlayerUlid($newState),
                    winnerUlid: $newState->winnerUlid,
                    phase: $newState->phase,
                    status: $newState->status,
                    board: $board,
                    isDraw: $newState->isDraw,
                );
            } else {
                $newState = new GameState(
                    players: $newState->players,
                    currentPlayerUlid: $this->getNextPlayerUlid($newState),
                    winnerUlid: $newState->winnerUlid,
                    phase: $newState->phase,
                    status: $newState->status,
                    board: $newState->board,
                    isDraw: $newState->isDraw,
                );
            }
        }

        return $newState;
    }

    /**
     * Apply a triple jump piece action.
     */
    protected function applyTripleJumpPiece(GameState $gameState, Actions\TripleJumpPiece $action): GameState
    {
        // Remove all three captured pieces
        $newState = $gameState
            ->withRemovedPiece($action->capturedRow1, $action->capturedCol1)
            ->withRemovedPiece($action->capturedRow2, $action->capturedCol2)
            ->withRemovedPiece($action->capturedRow3, $action->capturedCol3);

        // Move to final position
        $newState = $newState->withMovedPiece(
            $action->fromRow,
            $action->fromCol,
            $action->toRow,
            $action->toCol
        );

        // Check for king promotion
        $piece = $newState->getPieceAt($action->toRow, $action->toCol);
        if ($piece !== null) {
            $playerUlids = array_keys($newState->players);
            $shouldPromote = ($piece['player'] === $playerUlids[0] && $action->toRow === 7) ||
                           ($piece['player'] === $playerUlids[1] && $action->toRow === 0);

            if ($shouldPromote && ! $piece['king']) {
                $board = $newState->board;
                $board[$action->toRow][$action->toCol]['king'] = true;
                $newState = new GameState(
                    players: $newState->players,
                    currentPlayerUlid: $this->getNextPlayerUlid($newState),
                    winnerUlid: $newState->winnerUlid,
                    phase: $newState->phase,
                    status: $newState->status,
                    board: $board,
                    isDraw: $newState->isDraw,
                );
            } else {
                $newState = new GameState(
                    players: $newState->players,
                    currentPlayerUlid: $this->getNextPlayerUlid($newState),
                    winnerUlid: $newState->winnerUlid,
                    phase: $newState->phase,
                    status: $newState->status,
                    board: $newState->board,
                    isDraw: $newState->isDraw,
                );
            }
        }

        return $newState;
    }

    /**
     * Get the next player's ULID.
     */
    protected function getNextPlayerUlid(GameState $gameState): string
    {
        $playerUlids = array_keys($gameState->players);
        $currentIndex = array_search($gameState->currentPlayerUlid, $playerUlids);

        if ($currentIndex === false) {
            return $playerUlids[0];
        }

        $nextIndex = ($currentIndex + 1) % count($playerUlids);

        return $playerUlids[$nextIndex];
    }

    /**
     * Check if the game has ended.
     */
    public function checkEndCondition(object $gameState): GameOutcome
    {
        if (! ($gameState instanceof GameState)) {
            return GameOutcome::inProgress();
        }

        // Check for winner (no pieces remaining)
        foreach ($gameState->players as $player) {
            if ($player->piecesRemaining === 0) {
                // Other player wins
                $otherPlayers = array_filter(
                    $gameState->players,
                    fn ($p) => $p->ulid !== $player->ulid
                );
                $winner = reset($otherPlayers);

                if ($winner !== false) {
                    return GameOutcome::win($winner->ulid, null, 'no_pieces_remaining');
                }
            }
        }

        // Check for draw (no legal moves - stalemate)
        // This will be implemented more thoroughly in the game mode
        if ($gameState->isDraw) {
            return GameOutcome::draw('stalemate');
        }

        return GameOutcome::inProgress();
    }

    /**
     * Get available actions for a player.
     */
    public function getAvailableActions(object $gameState, string $playerUlid): array
    {
        // Will be implemented with game mode
        return [];
    }

    /**
     * Get the time limit in seconds.
     */
    public function getTimelimit(): int
    {
        return self::DEFAULT_TURN_TIME_SECONDS;
    }

    /**
     * Get the action deadline.
     */
    public function getActionDeadline(object $gameState, Game $game): Carbon
    {
        /** @var Action|null $lastAction */
        $lastAction = $game->actions()->latest()->first();
        $lastActionTime = $lastAction ? $lastAction->created_at : $game->created_at;

        return $lastActionTime->addSeconds(
            $this->getTimelimit() + self::NETWORK_GRACE_PERIOD_SECONDS
        );
    }

    /**
     * Get the timeout penalty.
     */
    public function getTimeoutPenalty(): string
    {
        return self::DEFAULT_TIMEOUT_PENALTY;
    }

    // GameReportingInterface implementation

    public function getPublicStatus(object $gameState): array
    {
        return [
            'pieces_remaining' => $this->getPieceCounts($gameState),
            'kings_count' => $this->getKingCounts($gameState),
        ];
    }

    public function describeStateChanges(Game $game, Action $action, object $gameState): array
    {
        $changes = parent::describeStateChanges($game, $action, $gameState);

        if ($this->wasKingPromoted($action)) {
            $changes[] = 'Piece promoted to King!';
        }
        if ($this->werePiecesCaptured($action)) {
            $captureCount = $this->countCapturedPieces($action);
            $changes[] = sprintf('%d opponent piece(s) captured', $captureCount);
        }

        return $changes;
    }

    public function formatActionSummary(Action $action): string
    {
        $username = $action->player->user->username;

        return match ($action->action_type->value) {
            'move_piece' => sprintf(
                '%s moved piece from [%d,%d] to [%d,%d]',
                $username,
                $action->action_details['from_row'] ?? 0,
                $action->action_details['from_col'] ?? 0,
                $action->action_details['to_row'] ?? 0,
                $action->action_details['to_col'] ?? 0
            ),
            'jump_piece' => sprintf(
                '%s jumped and captured opponent piece',
                $username
            ),
            'double_jump_piece' => sprintf(
                '%s performed a double jump, capturing 2 pieces',
                $username
            ),
            'triple_jump_piece' => sprintf(
                '%s performed a triple jump, capturing 3 pieces',
                $username
            ),
            default => parent::formatActionSummary($action),
        };
    }

    public function getFinishDetails(Game $game, GameOutcome $outcome, object $gameState): array
    {
        $details = parent::getFinishDetails($game, $outcome, $gameState);
        $details['final_piece_count'] = $this->getPieceCounts($gameState);

        $reason = $outcome->details['reason'] ?? null;
        if ($reason === 'no_pieces_remaining') {
            $details['reason_text'] = 'All opponent pieces captured';
        }

        return $details;
    }

    public function analyzeOutcome(Game $game, GameOutcome $outcome, object $gameState): array
    {
        $analysis = parent::analyzeOutcome($game, $outcome, $gameState);
        $analysis['dominant_victory'] = $this->wasVictoryDominant($gameState);

        return $analysis;
    }

    // Helpers

    protected function getPieceCounts(object $gameState): array
    {
        $counts = [];
        foreach ($gameState->players ?? [] as $ulid => $player) {
            $counts[$ulid] = $player->piecesRemaining ?? 0;
        }

        return $counts;
    }

    protected function getKingCounts(object $gameState): array
    {
        $counts = [];
        foreach ($gameState->board ?? [] as $row) {
            foreach ($row as $cell) {
                if ($cell !== null && isset($cell['king']) && $cell['king']) {
                    $playerUlid = $cell['player'] ?? null;
                    if ($playerUlid) {
                        $counts[$playerUlid] = ($counts[$playerUlid] ?? 0) + 1;
                    }
                }
            }
        }

        return $counts;
    }

    protected function wasKingPromoted(Action $action): bool
    {
        // Placeholder as per service
        return false;
    }

    protected function werePiecesCaptured(Action $action): bool
    {
        return in_array($action->action_type->value, ['jump_piece', 'double_jump_piece', 'triple_jump_piece']);
    }

    protected function countCapturedPieces(Action $action): int
    {
        return match ($action->action_type->value) {
            'jump_piece' => 1,
            'double_jump_piece' => 2,
            'triple_jump_piece' => 3,
            default => 0,
        };
    }

    protected function wasVictoryDominant(object $gameState): bool
    {
        $counts = $this->getPieceCounts($gameState);
        if (count($counts) < 2) {
            return false;
        }
        $values = array_values($counts);

        return max($values) > min($values) * 2;
    }
}
