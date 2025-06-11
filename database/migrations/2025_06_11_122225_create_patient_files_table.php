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
        Schema::create('patient_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('uploaded_by_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('file_type', ['image', 'document'])->default('document');
            $table->enum('category', ['xray', 'scan', 'lab_report', 'insurance', 'other'])->default('other');
            $table->string('original_filename');
            $table->string('stored_filename'); // UUID-based
            $table->string('file_path', 500);
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->string('mime_type', 100);
            $table->text('description')->nullable();
            $table->boolean('is_visible_to_patient')->default(true);
            $table->dateTime('uploaded_at');
            $table->timestamps();

            // Add indexes for faster lookups
            $table->index('patient_id');
            $table->index('uploaded_by_user_id');
            $table->index('file_type');
            $table->index('category');
            $table->index('is_visible_to_patient');
            $table->index('uploaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_files');
    }
};
