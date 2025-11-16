<?php

use App\Enums\Platform;
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
            $table->string('website')->nullable();
            $table->string('platform')->default(Platform::WEB->value);
            $table->boolean('is_active')->default(true);
            $table->foreignId('creator_id')->nullable()->index();
            $table->timestamps();
        });
    }
};
