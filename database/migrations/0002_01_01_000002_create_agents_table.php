<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();

            // Core Identity & Configuration
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('version', 20)->default('1.0.0');
            $table->tinyInteger('difficulty')->default(5)->comment('1-10 rating, 1=easiest, 10=hardest');
            $table->json('configuration')->nullable()->comment('AI-specific parameters');

            // AI Logic
            $table->string('ai_logic_path');
            $table->string('strategy_type', 50)->nullable()->comment('aggressive, defensive, balanced, random');
            $table->json('supported_game_titles')->nullable();

            // Availability
            $table->tinyInteger('available_hour_est')->nullable()->comment('Hour in EST when agent is available (0-23)');

            // Monitoring & Debugging
            $table->integer('error_count')->default(0);
            $table->timestamp('last_error_at')->nullable();
            $table->boolean('debug_mode')->default(false);

            $table->timestamps();
        });
    }
};
