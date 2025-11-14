<?php

namespace App\Models\Game;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'user_id',
        'name',
        'position_id',
        'color',
    ];

    protected $casts = [
        'position_id' => 'integer',
    ];

    // Relationships
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actions()
    {
        return $this->hasMany(Action::class);
    }
}
