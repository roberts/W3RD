<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique()->index();
            $table->string('title_slug', 50)->index();
            $table->foreignId('mode_id')->constrained('modes');
            $table->enum('status', ['pending', 'active', 'completed'])->default('pending');
            $table->foreignId('creator_id')->nullable()->constrained('users');

            // Outcome fields
            $table->foreignId('winner_id')->nullable()->constrained('users');
            $table->tinyInteger('winner_position')->nullable();       // Player position (1-4)
            $table->string('outcome_type', 20)->nullable();           // 'win', 'draw', 'forfeit', 'timeout'
            $table->json('outcome_details')->nullable();              // Flexible game-specific data

            $table->integer('turn_number')->default(0);
            $table->json('game_state');

            // Cached counters
            $table->tinyInteger('player_count')->default(0);
            $table->integer('action_count')->default(0);
            $table->integer('duration_seconds')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common queries
            $table->index(['status', 'created_at']);
            $table->index(['creator_id', 'status']);
            $table->index(['winner_id', 'completed_at']);
            $table->index('created_at');
        });
    }
};
