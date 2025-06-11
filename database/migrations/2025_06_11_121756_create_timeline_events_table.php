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
        Schema::create('timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->enum('event_type', ['appointment', 'prescription', 'vital_signs', 'note', 'file_upload', 'alert', 'manual'])->default('manual');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('event_date');
            $table->unsignedBigInteger('related_id')->nullable(); // ID of related record
            $table->string('related_type')->nullable(); // Model class name
            $table->enum('importance', ['low', 'medium', 'high'])->default('medium');
            $table->boolean('is_visible_to_patient')->default(true); // Based on note privacy
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Add indexes for faster lookups
            $table->index('patient_id');
            $table->index('event_type');
            $table->index('event_date');
            $table->index(['related_id', 'related_type']);
            $table->index('importance');
            $table->index('is_visible_to_patient');
            $table->index('created_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeline_events');
    }
};
