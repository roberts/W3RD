<?php

use App\Enums\AvatarType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avatars', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->foreignId('image_id')->nullable()->constrained('images');
            $table->string('type')->default(AvatarType::FREE->value);
            $table->foreignId('creator_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }
};
