<?php

namespace App\Models\Gamification;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTitleLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title_slug',
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
    protected $primaryKey = ['user_id', 'title_slug'];

    public $incrementing = false;

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
