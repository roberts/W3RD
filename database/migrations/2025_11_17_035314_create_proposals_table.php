<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique()->index();
            $table->foreignId('requesting_user_id')->constrained('users');
            $table->foreignId('opponent_user_id')->constrained('users');
            $table->string('title_slug')->nullable();
            $table->foreignId('mode_id')->nullable();
            $table->string('type')->default('rematch'); // rematch, casual, tournament
            $table->foreignId('original_game_id')->nullable()->constrained('games');
            $table->foreignId('game_id')->nullable()->constrained('games');
            $table->json('game_settings')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->string('status')->default('pending'); // pending, accepted, declined, expired
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }
};
