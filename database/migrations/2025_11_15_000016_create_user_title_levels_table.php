<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_title_levels', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->string('title_slug', 50);

            $table->tinyInteger('level')->default(1);
            $table->integer('xp_current')->default(0)->comment('XP toward next level');
            $table->timestamp('last_played_at')->useCurrent();

            $table->primary(['user_id', 'title_slug']);
            $table->timestamps();
        });
    }
};
