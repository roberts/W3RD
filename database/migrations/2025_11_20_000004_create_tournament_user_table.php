<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->integer('seed')->nullable()->comment('Bracket placement');
            $table->integer('rank')->nullable()->comment('Final placement');
            $table->decimal('winnings', 10, 2)->default(0.00);
            $table->enum('status', ['registered', 'active', 'eliminated', 'withdrew'])->default('registered');
            $table->timestamp('registered_at');

            $table->unique(['tournament_id', 'user_id']);
            $table->index(['tournament_id', 'status']);
        });
    }
};
