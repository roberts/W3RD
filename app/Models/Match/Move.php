<?php

namespace App\Models\Match;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Move extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'player_id',
        'turn_number',
        'move_details',
    ];

    protected $casts = [
        'move_details' => 'array',
        'turn_number' => 'integer',
    ];

    // Relationships
    public function match()
    {
        return $this->belongsTo(Match::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
