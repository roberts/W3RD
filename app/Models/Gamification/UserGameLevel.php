<?php

namespace App\Models\Gamification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class UserGameLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_slug',
        'level',
        'xp_current',
        'last_played_at',
    ];

    protected $casts = [
        'level' => 'integer',
        'xp_current' => 'integer',
        'last_played_at' => 'datetime',
    ];

    // Composite primary key
    protected $primaryKey = ['user_id', 'game_slug'];
    public $incrementing = false;

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
