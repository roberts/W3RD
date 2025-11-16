<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider_name'); // e.g., 'google', 'telegram', 'github'
            $table->string('provider_id');   // The user's unique ID from that provider
            $table->text('provider_token')->nullable(); // The access token from the provider, TEXT for longer tokens
            $table->text('provider_refresh_token')->nullable(); // For refreshing the access token, TEXT for longer tokens
            $table->timestamps();

            $table->unique(['provider_name', 'provider_id']); // Ensures no duplicate accounts
        });
    }
};
