<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Title extends Model
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
    public function games()
    {
        return $this->hasMany(Game::class, 'title_slug', 'slug');
    }
}
