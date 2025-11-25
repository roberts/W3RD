<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->decimal('tokens', 10, 2)->default(0.00)->comment('Virtual tokens for gameplay');
            $table->decimal('chips', 10, 2)->default(0.00)->comment('Virtual chips for gameplay');
            $table->decimal('locked_in_games', 10, 2)->default(0.00)->comment('Virtual currency in active games');
            $table->timestamp('updated_at');

            // Unique constraint: one balance per user per client
            $table->unique(['user_id', 'client_id']);

            // Index for client balance queries
            $table->index('client_id');
        });
    }
};
