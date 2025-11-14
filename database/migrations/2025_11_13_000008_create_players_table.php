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
            $table->foreignId('match_id')->constrained('matches');
            $table->foreignId('user_id')->constrained('users');
            $table->string('name', 50);
            $table->tinyInteger('position_id')->comment('Turn order: 1, 2, 3, 4');
            $table->string('color', 20);
            
            $table->unique(['match_id', 'position_id']);
            $table->timestamps();
        });
        
        // Add winner_id foreign key to matches table now that players exists
        Schema::table('matches', function (Blueprint $table) {
            $table->foreign('winner_id')->references('id')->on('players');
        });
    }
};
