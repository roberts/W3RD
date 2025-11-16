<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasFactory;

class Registration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'email',
        'password',
        'verification_token',
        'user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'verification_token',
    ];

    /**
     * Get the client that originated the registration.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the final user record associated with the registration.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
