<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('api_key', 64)->unique();
            $table->enum('platform', ['web', 'ios', 'android', 'electron', 'cli']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
