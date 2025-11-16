<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('game_id')->constrained('games');
            $table->foreignId('user_id')->constrained('users');
            $table->string('name', 50);
            $table->tinyInteger('position_id')->comment('Turn order: 1, 2, 3, 4');
            $table->string('color', 20);

            $table->unique(['game_id', 'position_id']);
            $table->unique(['game_id', 'user_id']); // Prevent same user joining twice
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['user_id', 'game_id']);
        });

        // Add winner_id foreign key to games table now that players exists
        Schema::table('games', function (Blueprint $table) {
            $table->foreign('winner_id')->references('id')->on('players');
        });
    }
};
