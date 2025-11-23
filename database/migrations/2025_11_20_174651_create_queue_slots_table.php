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
        Schema::create('queue_slots', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique()->index();
            $table->foreignId('user_id')->constrained('users');
            $table->string('title_slug', 50)->index();
            $table->foreignId('mode_id')->constrained('modes');
            $table->integer('skill_rating')->nullable();
            $table->string('status')->default('active'); // active, matched, cancelled, expired
            $table->foreignId('matched_lobby_id')->nullable()->constrained('lobbies');
            $table->json('preferences')->nullable(); // Additional matchmaking preferences
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'title_slug']);
            $table->index(['user_id', 'status']);
        });
    }
};
