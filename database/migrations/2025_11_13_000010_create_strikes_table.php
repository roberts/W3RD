<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('title_slug', 50);
            $table->tinyInteger('strikes_used')->default(0);
            $table->date('strike_date');

            $table->unique(['user_id', 'title_slug', 'strike_date']);
            $table->timestamps();
        });
    }
};
