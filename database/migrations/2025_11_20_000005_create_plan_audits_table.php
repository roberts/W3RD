<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('membership_plan');
            $table->date('day');
            
            // Daily strikes tracking (resets daily at midnight EST)
            $table->integer('strikes_used')->default(0);
            $table->integer('strikes_limit');
            
            // Monthly quota tracking (resets on 1st of month at midnight EST)
            $table->integer('quota_used')->default(0);
            $table->integer('quota_limit');
            
            $table->timestamps();
            
            // Unique constraint: one audit record per user per membership plan per day
            $table->unique(['user_id', 'membership_plan', 'day']);
            
            // Indexes for lookups
            $table->index(['user_id', 'day']);
            $table->index(['membership_plan', 'day']);
        });
    }
};
