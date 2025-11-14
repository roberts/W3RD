<?php

namespace App\Models\Content;

use App\Enums\AvatarType;
use App\Models\Auth\User;
use DrewRoberts\Media\Models\Image;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avatar extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_id',
        'type',
    ];

    protected $casts = [
        'type' => AvatarType::class,
    ];

    // Relationships
    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
