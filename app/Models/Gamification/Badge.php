<?php

namespace App\Models\Gamification;

use App\Models\Auth\User;
use DrewRoberts\Media\Models\Image;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Roberts\Support\Traits\HasCreator;

class Badge extends Model
{
    /** @use HasFactory<\Database\Factories\Gamification\BadgeFactory> */
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
    /**
     * @return BelongsTo<Image, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badge')
            ->withPivot('earned_at');
    }
}
