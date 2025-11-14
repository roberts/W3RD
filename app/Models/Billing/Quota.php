<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class Quota extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_slug',
        'matches_started',
        'reset_month',
    ];

    protected $casts = [
        'reset_month' => 'date',
        'matches_started' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
