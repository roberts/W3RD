<?php

declare(strict_types=1);

namespace App\Games\Hearts;

use App\Enums\GamePhase;
use App\Enums\GameStatus;
use App\Games\BaseGameState;

/**
 * Immutable game state for Hearts.
 *
 * This class extends BaseGameState and adds Hearts-specific state
 * (hands, tricks, rounds, and scoring).
 *
 * ## Game Phases
 * - DEALING: Cards are being dealt
 * - PASSING: Players select cards to pass
 * - TRICK_IN_PROGRESS: Players are playing cards in a trick
 * - ROUND_COMPLETE: Round finished, calculating scores
 * - GAME_OVER: Game finished
 *
 * ## Factory Methods
 * - `createNew(...$playerUlids)` - New game
 * - `fromArray($data)` - Restore from database JSON
 */
final class GameState extends BaseGameState
{
    /**
     * Current round number (1, 2, 3...).
     */
    public readonly int $roundNumber;

    /**
     * Player hands: maps player ULID to array of card codes.
     *
     * @var array<string, array<string>>
     */
    public readonly array $hands;

    /**
     * Current trick: maps player ULID to the card they played.
     *
     * @var array<string, string>
     */
    public readonly array $currentTrick;

    /**
     * ULID of the player who leads the current trick.
     */
    public readonly ?string $trickLeaderUlid;

    /**
     * Whether hearts have been "broken" (played) yet.
     */
    public readonly bool $heartsBroken;

    /**
     * Whether this is a draw game (should not occur in Hearts).
     */
    public readonly bool $isDraw;

    /**
     * Create a new Hearts game state.
     *
     * @param  array<string, PlayerState>  $players  Map of player ULID to PlayerState
     * @param  string|null  $currentPlayerUlid  ULID of current player
     * @param  string|null  $winnerUlid  ULID of winner
     * @param  GamePhase  $phase  Current game phase
     * @param  GameStatus  $status  Current game status
     * @param  int  $roundNumber  Current round number
     * @param  array<string, array<string>>  $hands  Player hands
     * @param  array<string, string>  $currentTrick  Current trick cards
     * @param  string|null  $trickLeaderUlid  Trick leader ULID
     * @param  bool  $heartsBroken  Hearts broken flag
     * @param  bool  $isDraw  Draw flag
     */
    public function __construct(
        array $players,
        ?string $currentPlayerUlid,
        ?string $winnerUlid,
        GamePhase $phase,
        GameStatus $status,
        int $roundNumber,
        array $hands,
        array $currentTrick = [],
        ?string $trickLeaderUlid = null,
        bool $heartsBroken = false,
        bool $isDraw = false,
    ) {
        parent::__construct($players, $currentPlayerUlid, $winnerUlid, $phase, $status);
        $this->roundNumber = $roundNumber;
        $this->hands = $hands;
        $this->currentTrick = $currentTrick;
        $this->trickLeaderUlid = $trickLeaderUlid;
        $this->heartsBroken = $heartsBroken;
        $this->isDraw = $isDraw;
    }

    /**
     * Create initial game state for a new Hearts game.
     *
     * @param  string  ...$playerUlids  Player ULIDs (must be exactly 4)
     * @return self New game state
     */
    public static function createNew(string ...$playerUlids): self
    {
        if (count($playerUlids) !== 4) {
            throw new \InvalidArgumentException('Hearts requires exactly 4 players');
        }

        $players = [];
        $hands = [];

        foreach ($playerUlids as $index => $ulid) {
            $players[$ulid] = new PlayerState($ulid, $index + 1, 0, 0);
            $hands[$ulid] = [];
        }

        return new self(
            players: $players,
            currentPlayerUlid: $playerUlids[0],
            winnerUlid: null,
            phase: GamePhase::SETUP,
            status: GameStatus::ACTIVE,
            roundNumber: 1,
            hands: $hands,
            currentTrick: [],
            trickLeaderUlid: null,
            heartsBroken: false,
            isDraw: false,
        );
    }

    /**
     * Create game state from array.
     *
     * @param  array<string, mixed>  $data  Serialized game state
     */
    public static function fromArray(array $data): static
    {
        $players = [];
        foreach ($data['players'] ?? [] as $ulid => $playerData) {
            $players[$ulid] = PlayerState::fromArray($playerData);
        }

        return new self(
            players: $players,
            currentPlayerUlid: $data['currentPlayerUlid'] ?? null,
            winnerUlid: $data['winnerUlid'] ?? null,
            phase: isset($data['phase']) ? GamePhase::from($data['phase']) : GamePhase::SETUP,
            status: isset($data['status']) ? GameStatus::from($data['status']) : GameStatus::ACTIVE,
            roundNumber: $data['roundNumber'] ?? 1,
            hands: $data['hands'] ?? [],
            currentTrick: $data['currentTrick'] ?? [],
            trickLeaderUlid: $data['trickLeaderUlid'] ?? null,
            heartsBroken: $data['heartsBroken'] ?? false,
            isDraw: $data['isDraw'] ?? false,
        );
    }

    /**
     * Convert to array for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'players' => array_map(fn ($p) => $p->toArray(), $this->players),
            'currentPlayerUlid' => $this->currentPlayerUlid,
            'winnerUlid' => $this->winnerUlid,
            'phase' => $this->phase->value,
            'status' => $this->status->value,
            'roundNumber' => $this->roundNumber,
            'hands' => $this->hands,
            'currentTrick' => $this->currentTrick,
            'trickLeaderUlid' => $this->trickLeaderUlid,
            'heartsBroken' => $this->heartsBroken,
            'isDraw' => $this->isDraw,
        ];
    }

    /**
     * Create new state with dealt hands.
     *
     * @param  array<string, array<string>>  $hands  New hands
     */
    public function withDealtHands(array $hands): self
    {
        return new self(
            players: $this->players,
            currentPlayerUlid: $this->currentPlayerUlid,
            winnerUlid: $this->winnerUlid,
            phase: GamePhase::SETUP, // Still in setup phase for passing
            status: $this->status,
            roundNumber: $this->roundNumber,
            hands: $hands,
            currentTrick: $this->currentTrick,
            trickLeaderUlid: $this->trickLeaderUlid,
            heartsBroken: $this->heartsBroken,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create new state with updated phase.
     *
     * @param  GamePhase  $phase  New phase
     */
    public function withPhase(GamePhase $phase): static
    {
        return new self(
            players: $this->players,
            currentPlayerUlid: $this->currentPlayerUlid,
            winnerUlid: $this->winnerUlid,
            phase: $phase,
            status: $this->status,
            roundNumber: $this->roundNumber,
            hands: $this->hands,
            currentTrick: $this->currentTrick,
            trickLeaderUlid: $this->trickLeaderUlid,
            heartsBroken: $this->heartsBroken,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create new state with updated status.
     *
     * @param  GameStatus  $status  New game status
     */
    public function withStatus(GameStatus $status): static
    {
        return new self(
            players: $this->players,
            currentPlayerUlid: $this->currentPlayerUlid,
            winnerUlid: $this->winnerUlid,
            phase: $this->phase,
            status: $status,
            roundNumber: $this->roundNumber,
            hands: $this->hands,
            currentTrick: $this->currentTrick,
            trickLeaderUlid: $this->trickLeaderUlid,
            heartsBroken: $this->heartsBroken,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Update game state to next player's turn.
     */
    public function withNextPlayer(): static
    {
        $playerUlids = $this->getPlayerUlids();
        $currentIndex = array_search($this->currentPlayerUlid, $playerUlids);
        $nextIndex = ($currentIndex + 1) % count($playerUlids);

        return new self(
            players: $this->players,
            currentPlayerUlid: $playerUlids[$nextIndex],
            winnerUlid: $this->winnerUlid,
            phase: $this->phase,
            status: $this->status,
            roundNumber: $this->roundNumber,
            hands: $this->hands,
            currentTrick: $this->currentTrick,
            trickLeaderUlid: $this->trickLeaderUlid,
            heartsBroken: $this->heartsBroken,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create new state with winner.
     *
     * @param  string  $winnerUlid  Winner's ULID
     */
    public function withWinner(string $winnerUlid): static
    {
        return new self(
            players: $this->players,
            currentPlayerUlid: null,
            winnerUlid: $winnerUlid,
            phase: GamePhase::COMPLETED,
            status: GameStatus::COMPLETED,
            roundNumber: $this->roundNumber,
            hands: $this->hands,
            currentTrick: $this->currentTrick,
            trickLeaderUlid: $this->trickLeaderUlid,
            heartsBroken: $this->heartsBroken,
            isDraw: $this->isDraw,
        );
    }
}
