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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_user_id')->constrained('users')->onDelete('cascade');

            $table->dateTime('appointment_datetime_start');
            $table->dateTime('appointment_datetime_end');

            $table->string('type')->nullable(); // consultation, follow-up, procedure
            $table->text('reason_for_visit')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, confirmed, completed, cancelled_by_patient, cancelled_by_clinic, no_show
            $table->text('cancellation_reason')->nullable();
            $table->text('notes_by_patient')->nullable();
            $table->text('notes_by_staff')->nullable();

            $table->foreignId('booked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->string('verification_code')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_user_id', 'appointment_datetime_start']);
            $table->index(['doctor_user_id', 'appointment_datetime_start']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
