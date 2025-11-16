<?php

namespace App\Models\Game;

use App\Enums\ActionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
