<?php

namespace App\Models\System;

use App\Enums\FeedbackStatus;
use App\Enums\FeedbackType;
use App\Models\Access\Client;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'email',
        'type',
        'content',
        'status',
        'metadata',
    ];

    protected $casts = [
        'type' => FeedbackType::class,
        'status' => FeedbackStatus::class,
        'metadata' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
