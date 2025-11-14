<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_daily_point_summaries', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->date('date')->index();
            $table->integer('points_earned')->default(0);
            
            $table->primary(['user_id', 'date']);
            $table->timestamps();
        });
    }
};
