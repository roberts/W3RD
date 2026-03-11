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
            $table->uuid('id')->primary(); // W3RD Context ID
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('workflow_id')->constrained('workflows');
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->string('email')->index();
            $table->json('form_data')->nullable(); // Encrypted blob of all step inputs
            $table->json('step_timings')->nullable(); // Analytics: Time spent per step
            $table->string('status')->default('draft'); // draft, pending_review, approved, graduated
            $table->string('intended_role')->nullable(); // Target User Role
            $table->uuid('parent_registration_uuid')->nullable()->index(); // For Team "Seat Holding"
            $table->foreignId('approved_by')->nullable()->constrained('users'); // Admin User ID who authorized graduation
            $table->timestamp('expires_at')->nullable(); // TTL for abandoned registrations
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['client_id', 'email']); // Scope email uniqueness to client
        });
    }
};
