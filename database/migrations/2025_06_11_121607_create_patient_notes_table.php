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
        Schema::create('patient_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->enum('note_type', ['general', 'diagnosis', 'treatment', 'follow_up'])->default('general');
            $table->string('title');
            $table->text('content');
            $table->boolean('is_private')->default(false); // Hide from patient if true
            $table->timestamps();

            // Add indexes for faster lookups
            $table->index('patient_id');
            $table->index('doctor_id');
            $table->index('note_type');
            $table->index('is_private');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_notes');
    }
};
