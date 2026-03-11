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
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->string('name');
            $table->string('category')->default('registration');
            $table->boolean('is_active')->default(true);
            $table->json('traffic_split')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
