<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lobby_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lobby_id')->constrained('lobbies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // note: not player model
            $table->foreignId('client_id')->nullable()->constrained('clients'); // record which client through which user joined lobby
            $table->string('status')->default('pending')->index();
            $table->string('source', 20)->default('invited')->index();
            $table->timestamps();

            $table->unique(['lobby_id', 'user_id']);
            $table->index(['lobby_id', 'status']);
        });
    }
};
