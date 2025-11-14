<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_monthly_point_summaries', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->string('month', 7);
            $table->integer('points_earned')->default(0);

            $table->primary(['user_id', 'month']);
            $table->timestamps();
        });
    }
};
