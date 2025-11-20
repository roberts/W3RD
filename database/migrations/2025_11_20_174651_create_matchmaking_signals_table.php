<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matchmaking_signals', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique()->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('game_preference')->nullable(); // game title slug
            $table->integer('skill_rating')->nullable();
            $table->string('status')->default('active'); // active, matched, cancelled, expired
            $table->json('preferences')->nullable(); // Additional matchmaking preferences
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'game_preference']);
            $table->index(['user_id', 'status']);
        });
    }
};
