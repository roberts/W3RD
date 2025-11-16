<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modes', function (Blueprint $table) {
            $table->id();
            $table->string('title_slug', 50)->index();
            $table->string('slug', 50);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraint: each title can only have one mode with a given slug
            $table->unique(['title_slug', 'slug']);
            $table->index(['title_slug', 'is_active']);
        });
    }
};
