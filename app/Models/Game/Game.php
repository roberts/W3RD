<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'max_players',
    ];

    protected $casts = [
        'max_players' => 'integer',
    ];

    // Relationships
    public function matches()
    {
        return $this->hasMany(\App\Models\Match\Match::class, 'game_slug', 'slug');
    }
}
