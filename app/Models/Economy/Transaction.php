<?php

namespace App\Models\Economy;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
 * @property array<string, mixed>|null $metadata
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
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model (e.g., Game, Subscription).
     *
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Generate ULIDs for the explicit column instead of the primary key.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Scope to find a transaction by ULID with optional eager loading.
     *
     * @param  array<int, string>  $with
     */
    public function scopeWithUlid(Builder $query, string $ulid, array $with = []): Builder
    {
        $query = $query->where('ulid', $ulid);

        if (! empty($with)) {
            $query->with($with);
        }

        return $query;
    }

    /**
     * Scope to filter transactions by currency type.
     */
    public function scopeForCurrency(Builder $query, string $currencyType): Builder
    {
        return $query->where('currency_type', $currencyType);
    }

    /**
     * Scope for credit transactions.
     */
    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('amount', '>', 0);
    }

    /**
     * Scope for debit transactions.
     */
    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('amount', '<', 0);
    }
}
