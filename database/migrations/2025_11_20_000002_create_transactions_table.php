<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique()->index();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
            
            // Transaction type: virtual balance OR real payment
            $table->enum('type', [
                // Virtual balance (entertainment only)
                'balance_add', 'balance_remove', 'game_buy_in', 'game_cash_out',
                // Real payments (subscriptions)
                'subscription_payment', 'subscription_refund',
                // Mobile IAP
                'iap_purchase', 'iap_refund'
            ]);
            
            $table->decimal('amount', 10, 2);
            
            // For virtual balance transactions
            $table->enum('currency', ['tokens', 'chips', 'usd'])->nullable();
            
            // Foreign key relationships for payment types
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            
            // For real payments
            $table->enum('payment_provider', ['stripe', 'google_play', 'apple_store', 'telegram'])->nullable();
            $table->string('provider_transaction_id')->nullable()->index()->comment('Stripe payment ID, Google order ID, etc.');
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->nullable();
            
            // Reconciliation and metadata
            $table->string('reference')->nullable()->comment('Client reference ID for balance operations');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for transaction history queries
            $table->index(['user_id', 'created_at']);
            $table->index(['client_id', 'created_at']);
            $table->index(['subscription_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('reference');
        });
    }
};
