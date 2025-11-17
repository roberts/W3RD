<?php

namespace App\Models\Game;

use App\Enums\ActionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $game_id
 * @property int $player_id
 * @property int $turn_number
 * @property ActionType $action_type
 * @property array|null $action_details
 * @property string|null $status
 * @property Player $player
 * @property Game $game
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Action extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'game_id',
        'player_id',
        'turn_number',
        'action_type',
        'action_details',
        'status',
        'error_code',
        'timestamp_client',
    ];

    protected $casts = [
        'action_type' => ActionType::class,
        'action_details' => 'array',
        'turn_number' => 'integer',
        'timestamp_client' => 'datetime',
    ];

    // Relationships
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
