<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique()->index();
            $table->string('title_slug', 50)->index();
            $table->foreignId('mode_id')->constrained();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('format', ['single_elimination', 'double_elimination', 'round_robin', 'swiss']);
            $table->decimal('buy_in_amount', 10, 2);
            $table->enum('buy_in_currency', ['tokens', 'chips'])->default('chips');
            $table->decimal('prize_pool', 10, 2);
            $table->json('prize_distribution');
            $table->integer('max_participants');
            $table->integer('min_participants');
            $table->enum('status', ['scheduled', 'registration_open', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->json('phase_rules')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['title_slug', 'status']);
        });
    }
};
