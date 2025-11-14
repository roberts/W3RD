<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');

            // Polymorphic relation to source (e.g., Game, Badge)
            $table->morphs('source');

            $table->integer('points')->comment('Positive (award) or negative (deduction)');
            $table->string('description', 100);
            $table->timestamps();
        });
    }
};
