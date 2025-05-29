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
        Schema::create('vital_signs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('blood_pressure_systolic')->nullable();
            $table->unsignedSmallInteger('blood_pressure_diastolic')->nullable();
            $table->unsignedSmallInteger('pulse_rate')->nullable(); // bpm
            $table->decimal('temperature', 4, 1)->nullable();
            $table->string('temperature_unit', 10)->default('°C');
            $table->unsignedSmallInteger('respiratory_rate')->nullable(); // breaths/min
            $table->unsignedSmallInteger('oxygen_saturation')->nullable(); // %
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('weight_unit', 10)->default('kg');
            $table->decimal('height', 5, 2)->nullable();
            $table->string('height_unit', 10)->default('cm');
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            // Add indexes for faster lookups
            $table->index('patient_id');
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vital_signs');
    }
};
