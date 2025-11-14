<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_badge', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('badge_id')->constrained('badges');
            $table->timestamp('earned_at')->useCurrent();
            
            $table->primary(['user_id', 'badge_id']);
        });
    }
};
