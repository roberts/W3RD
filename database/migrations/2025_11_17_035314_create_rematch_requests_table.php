<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rematch_requests', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique()->index();
            $table->foreignId('original_game_id')->constrained('games')->onDelete('cascade');
            $table->foreignId('requesting_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('opponent_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('new_game_id')->nullable()->constrained('games')->onDelete('set null');
            $table->string('status')->default('pending'); // pending, accepted, declined, expired
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }
};
