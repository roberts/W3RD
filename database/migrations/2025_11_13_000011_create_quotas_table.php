<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('title_slug', 50);
            $table->integer('games_started')->default(0);
            $table->date('reset_month');

            $table->unique(['user_id', 'title_slug', 'reset_month']);
            $table->timestamps();
        });
    }
};
