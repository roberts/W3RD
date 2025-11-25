<?php

namespace App\Models\Access;

use App\Enums\Platform;
use App\Models\Auth\Entry;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Roberts\Support\Traits\HasCreator;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\Access\ClientFactory> */
    use HasCreator, HasFactory;

    protected $fillable = [
        'name',
        'api_key',
        'platform',
        'is_active',
        'use_trademarks',
        'website',
        'creator_id',
    ];

    protected $casts = [
        'platform' => Platform::class,
        'is_active' => 'boolean',
        'use_trademarks' => 'boolean',
    ];

    public function getWebsiteLinkAttribute(): ?string
    {
        if ($this->website) {
            return 'https://'.$this->website;
        }

        return null;
    }

    // Relationships
    /**
     * @return HasMany<Entry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function registeredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'registration_client_id');
    }
}
