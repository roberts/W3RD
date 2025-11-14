<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->foreignId('image_id')->nullable()->constrained('images');
            $table->json('condition_json')->comment('Unlock criteria, e.g., {"wins": 10}');
            $table->timestamps();
        });
    }
};
