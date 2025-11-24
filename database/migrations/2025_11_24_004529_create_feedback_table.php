<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('type')->index();
            $table->text('content');
            $table->string('status')->default('pending')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
};
