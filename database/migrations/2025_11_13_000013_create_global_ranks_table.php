<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_ranks', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users');
            $table->integer('total_points')->default(0)->index();
            $table->integer('rank')->nullable();
            $table->timestamps();
        });
    }
};
