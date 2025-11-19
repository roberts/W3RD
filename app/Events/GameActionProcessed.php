<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Game\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameActionProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Game $game,
        public readonly string $actionType,
        public readonly array $actionDetails,
        public readonly string $playerUlid,
        public readonly string $actionUlid,
        public readonly array $actionContext = [],
        public readonly ?array $outcomeDetails = null,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel("game.{$this->game->ulid}");
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'action.processed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $data = [
            'action_ulid' => $this->actionUlid,
            'game_ulid' => $this->game->ulid,
            'action_type' => $this->actionType,
            'action_details' => $this->actionDetails,
            'player_ulid' => $this->playerUlid,
            'game_state' => $this->game->game_state,
            'status' => $this->game->status,
            'current_player_ulid' => $this->game->game_state['currentPlayerUlid'] ?? null,
            'winner_ulid' => $this->game->game_state['winnerUlid'] ?? null,
            'is_draw' => $this->game->game_state['isDraw'] ?? false,
            'timestamp' => now()->toISOString(),
        ];

        // Add rich context if available
        if (!empty($this->actionContext)) {
            $data['context'] = [
                'action_summary' => $this->actionContext['action_summary'] ?? null,
                'state_changes' => $this->actionContext['state_changes'] ?? [],
                'next_player' => $this->actionContext['next_player'] ?? null,
                'turn_info' => $this->actionContext['turn_info'] ?? [],
                'phase' => $this->actionContext['phase'] ?? 'active',
                'available_actions' => $this->actionContext['available_actions'] ?? [],
                'game_specific' => $this->actionContext['game_specific'] ?? [],
            ];

            // Add animation hints for clients
            $data['animation_hints'] = $this->generateAnimationHints();
            
            // Add sound effect suggestions
            $data['sound_effects'] = $this->generateSoundEffects();
        }

        // Add outcome details if game ended
        if ($this->outcomeDetails) {
            $data['outcome'] = $this->outcomeDetails;
        }

        return $data;
    }

    /**
     * Generate animation hints for client UI.
     */
    protected function generateAnimationHints(): array
    {
        $hints = [];

        switch ($this->actionType) {
            case 'drop_piece':
                $hints[] = [
                    'type' => 'drop',
                    'column' => $this->actionDetails['column'] ?? 0,
                    'duration_ms' => 500,
                ];
                break;

            case 'pop_out':
                $hints[] = [
                    'type' => 'pop_out',
                    'column' => $this->actionDetails['column'] ?? 0,
                    'duration_ms' => 600,
                ];
                break;

            case 'move_piece':
            case 'jump_piece':
            case 'double_jump_piece':
            case 'triple_jump_piece':
                $hints[] = [
                    'type' => 'move',
                    'from' => [
                        'row' => $this->actionDetails['from_row'] ?? 0,
                        'col' => $this->actionDetails['from_col'] ?? 0,
                    ],
                    'to' => [
                        'row' => $this->actionDetails['to_row'] ?? 0,
                        'col' => $this->actionDetails['to_col'] ?? 0,
                    ],
                    'duration_ms' => 400,
                ];

                // Add capture animations for jumps
                if (str_contains($this->actionType, 'jump')) {
                    $captureCount = match($this->actionType) {
                        'jump_piece' => 1,
                        'double_jump_piece' => 2,
                        'triple_jump_piece' => 3,
                        default => 0,
                    };

                    for ($i = 1; $i <= $captureCount; $i++) {
                        $hints[] = [
                            'type' => 'capture',
                            'position' => [
                                'row' => $this->actionDetails["captured_row_$i"] ?? $this->actionDetails['captured_row'] ?? 0,
                                'col' => $this->actionDetails["captured_col_$i"] ?? $this->actionDetails['captured_col'] ?? 0,
                            ],
                            'duration_ms' => 300,
                            'delay_ms' => 200 * $i,
                        ];
                    }
                }
                break;

            case 'play_card':
                $hints[] = [
                    'type' => 'play_card',
                    'card' => $this->actionDetails['card'] ?? '',
                    'duration_ms' => 400,
                ];
                break;
        }

        return $hints;
    }

    /**
     * Generate sound effect suggestions for client.
     */
    protected function generateSoundEffects(): array
    {
        $effects = [];

        // Add state change sounds
        if (!empty($this->actionContext['state_changes'])) {
            foreach ($this->actionContext['state_changes'] as $change) {
                if (str_contains($change, 'King')) {
                    $effects[] = 'king_promotion';
                } elseif (str_contains($change, 'captured')) {
                    $effects[] = 'piece_captured';
                } elseif (str_contains($change, 'Hearts have been broken')) {
                    $effects[] = 'hearts_broken';
                } elseif (str_contains($change, 'Trick completed')) {
                    $effects[] = 'trick_complete';
                }
            }
        }

        // Add action-specific sounds
        switch ($this->actionType) {
            case 'drop_piece':
                $effects[] = 'piece_drop';
                break;
            case 'pop_out':
                $effects[] = 'piece_pop';
                break;
            case 'jump_piece':
            case 'double_jump_piece':
            case 'triple_jump_piece':
                $effects[] = 'piece_jump';
                break;
            case 'play_card':
                $effects[] = 'card_play';
                break;
        }

        // Add game end sound
        if ($this->outcomeDetails) {
            $effects[] = 'game_complete';
        }

        return array_unique($effects);
    }
}
