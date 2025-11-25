<?php

namespace App\Models\Content;

use App\Enums\AvatarType;
use App\Models\Auth\User;
use DrewRoberts\Media\Models\Image;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Roberts\Support\Traits\HasCreator;

class Avatar extends Model
{
    /** @use HasFactory<\Database\Factories\Content\AvatarFactory> */
    use HasCreator, HasFactory;

    protected $fillable = [
        'name',
        'image_id',
        'type',
        'creator_id',
    ];

    protected $casts = [
        'type' => AvatarType::class,
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
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
