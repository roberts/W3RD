<?php

namespace App\Models\Economy;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $currency_type
 * @property int $amount
 * @property int $reserved_amount
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read int $availableBalance
 * @property-read User $user
 */
class Balance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency_type',
        'amount',
        'reserved_amount',
    ];

    protected $casts = [
        'amount' => 'integer',
        'reserved_amount' => 'integer',
    ];

    /**
     * Scope to filter balances by currency type.
     */
    public function scopeForCurrency(Builder $query, string $currencyType): Builder
    {
        return $query->where('currency_type', $currencyType);
    }

    /**
     * Get the user that owns this balance.
     *
     * @return BelongsTo<User, Balance>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get available (non-reserved) balance.
     */
    public function getAvailableBalanceAttribute(): int
    {
        return max(0, $this->amount - $this->reserved_amount);
    }

    /**
     * Reserve an amount from the balance.
     */
    public function reserve(int $amount): bool
    {
        if ($this->availableBalance < $amount) {
            return false;
        }

        $this->reserved_amount += $amount;
        $this->save();

        return true;
    }

    /**
     * Release a reserved amount.
     */
    public function release(int $amount): void
    {
        $this->reserved_amount = max(0, $this->reserved_amount - $amount);
        $this->save();
    }

    /**
     * Add to balance.
     */
    public function credit(int $amount): void
    {
        $this->amount += $amount;
        $this->save();
    }

    /**
     * Deduct from balance (including reserved).
     */
    public function debit(int $amount): bool
    {
        if ($this->amount < $amount) {
            return false;
        }

        $this->amount -= $amount;
        $this->reserved_amount = max(0, $this->reserved_amount - $amount);
        $this->save();

        return true;
    }
}
