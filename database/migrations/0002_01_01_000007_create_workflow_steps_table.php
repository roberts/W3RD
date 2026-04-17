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
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows');
            $table->string('slug')->index();
            $table->string('type')->default('form'); // e.g. form, kyc, gate, review, payment, info, enrichment, game
            $table->string('blade_view');
            $table->json('logic_rule')->nullable();
            $table->json('risk_rule')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
