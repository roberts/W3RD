<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games');
            $table->foreignId('player_id')->constrained('players');
            
            // Core Action Data
            $table->integer('turn_number');
            $table->string('action_type', 50)->index(); // e.g., 'play_card', 'drop_piece', 'pass'
            $table->json('action_details'); // The core payload of the action
            
            // Validation and Integrity
            $table->enum('status', ['success', 'invalid', 'error'])->default('success');
            $table->string('error_code', 50)->nullable();
            
            // Temporal Data
            $table->timestamp('timestamp_client')->nullable();
            $table->timestamps();
        });
    }
};
