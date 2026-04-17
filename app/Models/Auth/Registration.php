<?php

namespace App\Models\Auth;

use App\Models\Access\Client;
use Database\Factories\Auth\RegistrationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $password
 * @property string|null $verification_token
 */
class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use HasFactory, HasUuids;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'client_id',
        'workflow_id',
        'current_step_id',
        'email',
        'password',
        'verification_token',
        'form_data',
        'step_timings',
        'status',
        'intended_role',
        'parent_registration_uuid',
        'approved_by',
        'expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'verification_token',
        'form_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'form_data' => 'encrypted:array',
        'step_timings' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Virtual attribute for backward compatibility.
     */
    public function getPasswordAttribute(): ?string
    {
        return $this->form_data['password'] ?? null;
    }

    /**
     * Virtual attribute for backward compatibility.
     */
    public function setPasswordAttribute(string $value): void
    {
        $data = $this->form_data ?? [];
        $data['password'] = $value;
        $this->form_data = $data;
    }

    /**
     * Get the client that originated the registration.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the final user record associated with the registration.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
