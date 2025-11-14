<?php

namespace App\Models\Gamification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'image_url',
        'condition_json',
    ];

    protected $casts = [
        'condition_json' => 'array',
    ];

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badge')
            ->withPivot('earned_at');
    }
}
