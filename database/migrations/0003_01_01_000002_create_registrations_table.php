<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('verification_token')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }
};
