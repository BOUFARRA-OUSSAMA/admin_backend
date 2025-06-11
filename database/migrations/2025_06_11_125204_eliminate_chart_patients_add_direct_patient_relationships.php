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
        // First, drop foreign key constraints to chart_patients
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['chart_patient_id']);
        });

        Schema::table('treatments', function (Blueprint $table) {
            $table->dropForeign(['chart_patient_id']);
        });

        Schema::table('lab_tests', function (Blueprint $table) {
            $table->dropForeign(['chart_patient_id']);
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropForeign(['chart_patient_id']);
        });

        // Now we can safely drop the chart_patients table
        Schema::dropIfExists('chart_patients');

        // Add patient_id to prescriptions table for direct relationship
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('cascade')->after('chart_patient_id');
            // Make chart_patient_id nullable since we're moving to direct relationships
            $table->unsignedBigInteger('chart_patient_id')->nullable()->change();
            $table->index('patient_id');
        });

        // Add patient_id to treatments table for direct relationship
        Schema::table('treatments', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('cascade')->after('chart_patient_id');
            // Make chart_patient_id nullable since we're moving to direct relationships
            $table->unsignedBigInteger('chart_patient_id')->nullable()->change();
            $table->index('patient_id');
        });

        // Add patient_id to lab_tests table for direct relationship
        Schema::table('lab_tests', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('cascade')->after('chart_patient_id');
            // Make chart_patient_id nullable since we're moving to direct relationships
            $table->unsignedBigInteger('chart_patient_id')->nullable()->change();
            $table->index('patient_id');
        });

        // Add patient_id to lab_results table for direct relationship
        Schema::table('lab_results', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('cascade')->after('lab_test_id');
            $table->index('patient_id');
        });

        // Note: medical_records already has patient_user_id, so no changes needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate chart_patients table
        Schema::create('chart_patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_user_id')->constrained('users')->onDelete('cascade');
            $table->text('chief_complaint');
            $table->text('diagnosis')->nullable();
            $table->string('status')->default('active');
            $table->date('followup_date')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('patient_user_id');
            $table->index('status');
            $table->index('followup_date');
        });

        // Remove patient_id columns
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
        });

        Schema::table('treatments', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
        });

        Schema::table('lab_tests', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
        });

        Schema::table('lab_results', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
        });

        // Recreate foreign key constraints to chart_patients
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreign('chart_patient_id')->references('id')->on('chart_patients')->onDelete('cascade');
        });

        Schema::table('treatments', function (Blueprint $table) {
            $table->foreign('chart_patient_id')->references('id')->on('chart_patients')->onDelete('cascade');
        });

        Schema::table('lab_tests', function (Blueprint $table) {
            $table->foreign('chart_patient_id')->references('id')->on('chart_patients')->onDelete('cascade');
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->foreign('chart_patient_id')->references('id')->on('chart_patients')->nullOnDelete();
        });
    }
};
