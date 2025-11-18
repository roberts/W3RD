<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lobbies', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique()->index();
            $table->string('game_title')->index();
            $table->string('game_mode')->nullable();
            $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_public')->default(false)->index();
            $table->unsignedTinyInteger('min_players')->default(2);
            $table->timestamp('scheduled_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('game_id')->nullable()->constrained('games');
            $table->timestamps();

            $table->index(['game_title', 'is_public', 'status']);
        });
    }
};
