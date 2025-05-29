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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_user_id')->constrained('users')->comment('The patient this record belongs to');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete()->comment('User who created this record');
            $table->foreignId('record_type_id')->constrained('record_types');
            $table->foreignId('chart_patient_id')->nullable()->constrained('chart_patients')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('record_date');
            $table->boolean('is_confidential')->default(false);
            $table->string('status')->default('active'); // active, archived, pending_review
            $table->string('version')->default('1.0');

            // For polymorphic relationships
            $table->string('recordable_type')->nullable();
            $table->unsignedBigInteger('recordable_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Add indexes for faster lookups
            $table->index('patient_user_id');
            $table->index('created_by_user_id');
            $table->index('record_type_id');
            $table->index('chart_patient_id');
            $table->index('record_date');
            $table->index('status');
            $table->index(['recordable_type', 'recordable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
