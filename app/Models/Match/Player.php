<?php

namespace App\Models\Match;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'user_id',
        'name',
        'position_id',
        'color',
    ];

    protected $casts = [
        'position_id' => 'integer',
    ];

    // Relationships
    public function match()
    {
        return $this->belongsTo(\App\Models\Match\Match::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moves()
    {
        return $this->hasMany(Move::class);
    }
}
