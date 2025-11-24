<?php

namespace App\Services\Economy;

use App\Models\Auth\User;
use App\Models\Economy\Balance;
use App\Models\Economy\Transaction;
use Illuminate\Support\Facades\DB;

class EconomyService
{
    /**
     * Adjust user balance and record transaction.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function adjustBalance(
        int $userId,
        string $currencyType,
        int $amount,
        string $description,
        array $metadata = []
    ): Transaction {
        return DB::transaction(function () use ($userId, $currencyType, $amount, $description, $metadata) {
            // Get or create balance
            $balance = Balance::firstOrCreate(
                [
                    'user_id' => $userId,
                    'currency_type' => $currencyType,
                ],
                [
                    'amount' => 0,
                    'reserved_amount' => 0,
                ]
            );

            // Update balance
            if ($amount > 0) {
                $balance->credit($amount);
                $transactionType = 'credit';
            } else {
                if (! $balance->debit(abs($amount))) {
                    throw new \Exception('Insufficient balance');
                }
                $transactionType = 'debit';
            }

            // Record transaction
            $transaction = Transaction::create([
                'user_id' => $userId,
                'currency_type' => $currencyType,
                'amount' => $amount,
                'transaction_type' => $transactionType,
                'description' => $description,
                'metadata' => $metadata,
            ]);

            return $transaction;
        });
    }

    /**
     * Get user balance for a currency type.
     */
    public function getBalance(int $userId, string $currencyType): int
    {
        $balance = Balance::where('user_id', $userId)
            ->forCurrency($currencyType)
            ->first();

        return $balance->amount ?? 0;
    }

    /**
     * Reserve balance for a pending transaction (e.g., tournament buy-in).
     */
    public function reserveBalance(int $userId, string $currencyType, int $amount): bool
    {
        $balance = Balance::where('user_id', $userId)
            ->forCurrency($currencyType)
            ->first();

        if (! $balance) {
            return false;
        }

        return $balance->reserve($amount);
    }

    /**
     * Release reserved balance.
     */
    public function releaseReserved(int $userId, string $currencyType, int $amount): void
    {
        $balance = Balance::where('user_id', $userId)
            ->forCurrency($currencyType)
            ->first();

        if ($balance) {
            $balance->release($amount);
        }
    }

    /**
     * Transfer balance from reserved to actual deduction.
     */
    public function captureReserved(int $userId, string $currencyType, int $amount, string $description): Transaction
    {
        return DB::transaction(function () use ($userId, $currencyType, $amount, $description) {
            $balance = Balance::where('user_id', $userId)
                ->forCurrency($currencyType)
                ->firstOrFail();

            // Deduct from both total and reserved
            $balance->debit($amount);

            // Record transaction
            return Transaction::create([
                'user_id' => $userId,
                'currency_type' => $currencyType,
                'amount' => -$amount,
                'transaction_type' => 'debit',
                'description' => $description,
            ]);
        });
    }
}
