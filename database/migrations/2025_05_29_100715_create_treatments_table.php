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
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chart_patient_id')->constrained('chart_patients')->onDelete('cascade');
            $table->foreignId('prescribed_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('treatment_name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('instructions')->nullable();
            $table->string('duration')->nullable();
            $table->string('status')->default('ongoing'); // ongoing, completed, cancelled
            $table->timestamps();

            // Add indexes for faster lookups
            $table->index('chart_patient_id');
            $table->index('prescribed_by_user_id');
            $table->index('status');
            $table->index('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
