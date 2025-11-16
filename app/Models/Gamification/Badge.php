<?php

namespace App\Models\Gamification;

use App\Models\Auth\User;
use DrewRoberts\Media\Models\Image;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Roberts\Support\Traits\HasCreator;

class Badge extends Model
{
    use HasCreator, HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'image_id',
        'condition_json',
        'creator_id',
    ];

    protected $casts = [
        'condition_json' => 'array',
    ];

    // Relationships
    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badge')
            ->withPivot('earned_at');
    }
}
