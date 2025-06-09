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
        Schema::create('reminder_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('analytics_date');
            $table->foreignId('doctor_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // Daily reminder metrics
            $table->integer('reminders_sent')->default(0);
            $table->integer('reminders_delivered')->default(0);
            $table->integer('reminders_failed')->default(0);
            $table->integer('reminders_opened')->default(0);
            $table->integer('reminders_clicked')->default(0);
            
            // Channel breakdown
            $table->integer('email_sent')->default(0);
            $table->integer('push_sent')->default(0);
            $table->integer('sms_sent')->default(0);
            $table->integer('in_app_sent')->default(0);
            
            // Appointment outcomes
            $table->integer('appointments_kept')->default(0);
            $table->integer('appointments_cancelled')->default(0);
            $table->integer('appointments_no_show')->default(0);
            $table->integer('appointments_rescheduled')->default(0);
            
            // Effectiveness metrics
            $table->decimal('delivery_rate', 5, 2)->default(0); // Percentage
            $table->decimal('open_rate', 5, 2)->default(0);     // Percentage
            $table->decimal('click_rate', 5, 2)->default(0);    // Percentage
            $table->decimal('attendance_rate', 5, 2)->default(0); // Percentage
            
            // Response times (in minutes)
            $table->integer('avg_response_time')->nullable();
            $table->integer('fastest_response_time')->nullable();
            $table->integer('slowest_response_time')->nullable();
            
            $table->timestamps();
            
            $table->unique(['analytics_date', 'doctor_id']);
            $table->index('analytics_date');
            $table->index(['doctor_id', 'analytics_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder_analytics');
    }
};
