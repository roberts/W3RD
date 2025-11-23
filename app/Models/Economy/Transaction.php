<?php

namespace App\Models\Economy;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property string $currency_type
 * @property int $amount
 * @property string $transaction_type
 * @property string|null $description
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 * @property-read Model|null $reference
 */
class Transaction extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid',
        'user_id',
        'currency_type',
        'amount',
        'transaction_type',
        'description',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the polymorphic reference (e.g., Game, Tournament).
     */
    public function reference()
    {
        return $this->morphTo();
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Generate ULIDs for the explicit column instead of the primary key.
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Scope for credit transactions.
     */
    public function scopeCredits($query)
    {
        return $query->where('amount', '>', 0);
    }

    /**
     * Scope for debit transactions.
     */
    public function scopeDebits($query)
    {
        return $query->where('amount', '<', 0);
    }
}
