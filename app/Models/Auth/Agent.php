<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_logic_path',
        'available_hour_est',
    ];

    protected $casts = [
        'available_hour_est' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->hasOne(User::class);
    }
}
