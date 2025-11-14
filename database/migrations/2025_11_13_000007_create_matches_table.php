<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique()->index();
            $table->string('game_slug', 50)->index();
            $table->enum('status', ['pending', 'active', 'finished'])->default('pending');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users');
            $table->unsignedBigInteger('winner_id')->nullable();
            $table->integer('turn_number')->default(0);
            $table->json('game_state');
            $table->timestamps();
        });
    }
};
